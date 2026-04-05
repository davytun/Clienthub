<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwnerAndBusiness(): array
    {
        $business = Business::create(['name' => 'Agency']);
        $owner    = User::create([
            'business_id' => $business->id,
            'name'        => 'Owner',
            'email'       => 'owner@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $business->update(['owner_id' => $owner->id]);

        return [$business, $owner];
    }

    public function test_client_invitation_is_sent_successfully(): void
    {
        Mail::fake();

        [$business, $owner] = $this->makeOwnerAndBusiness();

        $this->actingAs($owner)
            ->post('/clients/invite', [
                'name'  => 'Alice',
                'email' => 'alice@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email'       => 'alice@example.com',
            'role'        => 'client',
            'business_id' => $business->id,
        ]);

        Mail::assertQueued(\App\Mail\ClientInvitationMail::class);
    }

    public function test_invitation_token_is_single_use(): void
    {
        [$business, $owner] = $this->makeOwnerAndBusiness();

        $client = User::create([
            'business_id'      => $business->id,
            'name'             => 'Bob',
            'email'            => 'bob@example.com',
            'role'             => 'client',
            'invitation_token' => 'test-token-123',
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.invitation.accept',
            now()->addHours(72),
            ['token' => 'test-token-123']
        );

        // First activation
        $this->post($signedUrl, [
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $client->refresh();
        $this->assertNull($client->invitation_token, 'Token should be cleared after use');
        $this->assertNotNull($client->invitation_accepted_at);

        // Second attempt with same signed URL should fail (token is gone)
        $this->post($signedUrl, [
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertStatus(404); // firstOrFail() fails — user has no token anymore
    }

    public function test_client_can_login_after_accepting_invitation(): void
    {
        [$business, $owner] = $this->makeOwnerAndBusiness();

        $client = User::create([
            'business_id'            => $business->id,
            'name'                   => 'Charlie',
            'email'                  => 'charlie@example.com',
            'password'               => bcrypt('MyPassword1!'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);

        $this->post('/client/login', [
            'email'    => 'charlie@example.com',
            'password' => 'MyPassword1!',
        ])->assertRedirect('/client/dashboard');

        $this->assertAuthenticatedAs($client, 'client');
    }

    public function test_staff_account_cannot_login_via_client_portal(): void
    {
        [$business, $owner] = $this->makeOwnerAndBusiness();

        // Owner tries to log in through the client login form
        $this->post('/client/login', [
            'email'    => 'owner@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('client');
    }

    public function test_expired_invitation_link_is_rejected(): void
    {
        [$business, $owner] = $this->makeOwnerAndBusiness();

        $client = User::create([
            'business_id'      => $business->id,
            'name'             => 'Dave',
            'email'            => 'dave@example.com',
            'role'             => 'client',
            'invitation_token' => 'expired-token',
        ]);

        // Build a URL that's already expired
        $expiredUrl = URL::temporarySignedRoute(
            'client.invitation.accept',
            now()->subHour(),
            ['token' => 'expired-token']
        );

        $this->get($expiredUrl)->assertStatus(403);
    }
}
