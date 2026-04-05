<?php

namespace Tests\Feature;

use App\Mail\StaffInvitationMail;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->business = Business::create(['name' => 'Agency']);
        $this->owner    = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Owner',
            'email'                  => 'owner@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'owner',
            'invitation_accepted_at' => now(),
        ]);
        $this->business->update(['owner_id' => $this->owner->id]);
    }

    public function test_owner_can_view_team_page(): void
    {
        $this->actingAs($this->owner)
            ->get('/staff')
            ->assertOk()
            ->assertSee('Owner');
    }

    public function test_staff_member_cannot_view_team_page(): void
    {
        $staff = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Staff',
            'email'                  => 'staff@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'staff',
            'invitation_accepted_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get('/staff')
            ->assertStatus(403);
    }

    public function test_owner_can_invite_staff_member(): void
    {
        $this->actingAs($this->owner)
            ->post('/staff/invite', [
                'name'  => 'Alice',
                'email' => 'alice@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email'       => 'alice@example.com',
            'role'        => 'staff',
            'business_id' => $this->business->id,
        ]);

        Mail::assertQueued(StaffInvitationMail::class, function ($mail) {
            return $mail->hasTo('alice@example.com');
        });
    }

    public function test_staff_cannot_invite_other_staff(): void
    {
        $staff = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Staff',
            'email'                  => 'staff@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'staff',
            'invitation_accepted_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post('/staff/invite', ['name' => 'Bob', 'email' => 'bob@example.com'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
    }

    public function test_staff_invitation_accept_flow(): void
    {
        $member = User::create([
            'business_id'      => $this->business->id,
            'name'             => 'Bob',
            'email'            => 'bob@example.com',
            'role'             => 'staff',
            'invitation_token' => 'staff-token-xyz',
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['token' => 'staff-token-xyz']
        );

        $this->get($signedUrl)->assertOk();

        $this->post($signedUrl, [
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('dashboard'));

        $member->refresh();
        $this->assertNull($member->invitation_token);
        $this->assertNotNull($member->invitation_accepted_at);
        $this->assertAuthenticatedAs($member, 'web');
    }

    public function test_expired_staff_invitation_is_rejected(): void
    {
        User::create([
            'business_id'      => $this->business->id,
            'name'             => 'Carol',
            'email'            => 'carol@example.com',
            'role'             => 'staff',
            'invitation_token' => 'expired-staff-token',
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->subHour(),
            ['token' => 'expired-staff-token']
        );

        $this->get($expiredUrl)->assertStatus(403);
    }

    public function test_owner_can_remove_staff_member(): void
    {
        $staff = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Dave',
            'email'                  => 'dave@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'staff',
            'invitation_accepted_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->delete("/staff/{$staff->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_owner_cannot_remove_themselves(): void
    {
        $this->actingAs($this->owner)
            ->delete("/staff/{$this->owner->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }
}
