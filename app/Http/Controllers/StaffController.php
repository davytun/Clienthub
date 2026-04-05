<?php

namespace App\Http\Controllers;

use App\Mail\StaffInvitationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isOwner(), 403);

        $staff = User::where('business_id', auth()->user()->business_id)
            ->whereIn('role', ['owner', 'staff'])
            ->orderBy('name')
            ->get();

        return view('staff.index', compact('staff'));
    }

    public function invite(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isOwner(), 403);

        // 5 staff invitations per hour
        $throttleKey = 'staff-invite:' . auth()->id();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many invitations. Please wait {$seconds} seconds.",
            ]);
        }
        RateLimiter::hit($throttleKey, 3600);

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $business = auth()->user()->business;

        $member = User::create([
            'business_id'      => $business->id,
            'name'             => $request->name,
            'email'            => $request->email,
            'role'             => 'staff',
            'invitation_token' => Str::random(64),
        ]);

        $inviteUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['token' => $member->invitation_token]
        );

        Mail::to($member->email)->queue(new StaffInvitationMail($member, $business, $inviteUrl));

        return back()->with('success', "Invitation sent to {$member->name}.");
    }

    public function accept(Request $request, string $token): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        $member = User::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->firstOrFail();

        return view('staff.accept-invitation', compact('member', 'token'));
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $member = \Illuminate\Support\Facades\DB::transaction(function () use ($token, $request) {
            $member = User::where('invitation_token', $token)
                ->whereNull('invitation_accepted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $member->update([
                'password'               => $request->password,
                'invitation_token'       => null,
                'invitation_accepted_at' => now(),
            ]);

            return $member;
        });

        auth()->login($member);

        return redirect()->route('dashboard');
    }

    public function destroy(User $member): RedirectResponse
    {
        abort_unless(auth()->user()->isOwner(), 403);
        abort_unless($member->business_id === auth()->user()->business_id, 403);
        // Cannot remove yourself
        abort_if($member->id === auth()->id(), 422);
        // Cannot remove the only owner
        abort_if($member->isOwner(), 422);

        $member->delete();

        return back()->with('success', "{$member->name} has been removed from your team.");
    }
}
