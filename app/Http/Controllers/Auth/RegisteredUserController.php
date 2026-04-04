<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password'      => ['required', 'confirmed', Password::defaults()],
        ]);

        // Wrap in a transaction — both records must succeed together.
        $user = DB::transaction(function () use ($request) {
            // 1. Create the business (owner_id set after user is created)
            $business = Business::create([
                'name'  => $request->business_name,
            ]);

            // 2. Create the owner user, linked to the business
            $user = User::create([
                'business_id' => $business->id,
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => $request->password, // cast to hashed automatically
                'role'        => 'owner',
            ]);

            // 3. Link the business back to its owner
            $business->update(['owner_id' => $user->id]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
