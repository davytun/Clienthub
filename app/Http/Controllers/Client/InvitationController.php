<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ClientInvitationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Send an invitation email to a new client.
     * Called by staff from the clients index.
     */
    public function send(Request $request): RedirectResponse
    {
        // 10 invitations per staff member per hour
        $throttleKey = 'invite:' . auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many invitations sent. Please wait {$seconds} seconds before sending another.",
            ]);
        }
        RateLimiter::hit($throttleKey, 3600);

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $business = auth()->user()->business;

        $client = User::create([
            'business_id'      => $business->id,
            'name'             => $request->name,
            'email'            => $request->email,
            'role'             => 'client',
            'invitation_token' => Str::random(64),
        ]);

        // Signed URL expires in 72 hours. Single-use enforced by invitation_accepted_at being null.
        $inviteUrl = URL::temporarySignedRoute(
            'client.invitation.accept',
            now()->addHours(72),
            ['token' => $client->invitation_token]
        );

        Mail::to($client->email)->queue(new ClientInvitationMail($client, $business, $inviteUrl));

        return back()->with('success', "Invitation sent to {$client->name}.");
    }

    /**
     * Re-generate and re-send an invitation for a pending (unaccepted) client.
     */
    public function resend(User $client): RedirectResponse
    {
        // Only pending clients belonging to the authenticated business
        abort_unless($client->business_id === auth()->user()->business_id, 403);
        abort_if($client->hasAcceptedInvitation(), 422);

        // 3 resends per staff member per hour
        $throttleKey = 'resend-invite:' . auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->with('error', 'Too many resends. Please wait before trying again.');
        }
        RateLimiter::hit($throttleKey, 3600);

        $business = auth()->user()->business;

        $client->update(['invitation_token' => Str::random(64)]);

        $inviteUrl = URL::temporarySignedRoute(
            'client.invitation.accept',
            now()->addHours(72),
            ['token' => $client->invitation_token]
        );

        Mail::to($client->email)->queue(new ClientInvitationMail($client, $business, $inviteUrl));

        return back()->with('success', "Invitation resent to {$client->name}.");
    }

    /**
     * Show the "set your password" form when client clicks the invite link.
     */
    public function accept(Request $request, string $token): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        $client = User::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->firstOrFail();

        return view('client.auth.accept-invitation', compact('client', 'token'));
    }

    /**
     * Store the new password and activate the client account.
     */
    public function activate(Request $request, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Wrap in a transaction with a row lock to prevent two concurrent requests
        // from both activating the same invitation token (TOCTOU race condition).
        $client = DB::transaction(function () use ($token, $request) {
            $client = User::where('invitation_token', $token)
                ->whereNull('invitation_accepted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $client->update([
                'password'               => $request->password,
                'invitation_token'       => null,
                'invitation_accepted_at' => now(),
            ]);

            return $client;
        });

        auth()->guard('client')->login($client);

        return redirect()->route('client.dashboard');
    }
}
