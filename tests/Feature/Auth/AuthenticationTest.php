<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate(): void
    {
        $business = Business::create(['name' => 'Test Co']);
        $user     = User::create([
            'business_id' => $business->id,
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);

        $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_wrong_password(): void
    {
        $business = Business::create(['name' => 'Test Co']);
        User::create([
            'business_id' => $business->id,
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);

        $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $business = Business::create(['name' => 'Test Co']);
        $user     = User::create([
            'business_id' => $business->id,
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
