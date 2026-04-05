<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_business_and_owner(): void
    {
        $response = $this->post('/register', [
            'business_name'         => 'Acme Law Firm',
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('businesses', ['name' => 'Acme Law Firm']);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role'  => 'owner',
        ]);

        // Business owner_id must point back to the user
        $user     = User::where('email', 'john@example.com')->first();
        $business = Business::where('name', 'Acme Law Firm')->first();

        $this->assertEquals($business->owner_id, $user->id);
        $this->assertEquals($user->business_id, $business->id);
    }

    public function test_registration_requires_business_name(): void
    {
        $this->post('/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertSessionHasErrors('business_name');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $business = Business::create(['name' => 'Existing Co']);
        User::create([
            'business_id' => $business->id,
            'name'        => 'Existing',
            'email'       => 'taken@example.com',
            'role'        => 'owner',
            'password'    => bcrypt('password'),
        ]);

        $this->post('/register', [
            'business_name'         => 'New Co',
            'name'                  => 'New Person',
            'email'                 => 'taken@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertSessionHasErrors('email');
    }
}
