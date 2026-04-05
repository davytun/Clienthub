<?php

namespace Tests\Feature;

use App\Mail\NewMessageMail;
use App\Models\Business;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $client;
    private Business $business;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->business = Business::create(['name' => 'Agency']);
        $this->owner    = User::create([
            'business_id' => $this->business->id,
            'name'        => 'Owner',
            'email'       => 'owner@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $this->business->update(['owner_id' => $this->owner->id]);

        $this->client = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Client',
            'email'                  => 'client@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);

        $this->project = Project::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'client_id'   => $this->client->id,
            'title'       => 'Test Project',
            'status'      => 'active',
        ]);
    }

    public function test_staff_can_send_message_on_project(): void
    {
        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/messages", ['body' => 'Hello client!'])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'project_id' => $this->project->id,
            'sender_id'  => $this->owner->id,
            'body'       => 'Hello client!',
        ]);
    }

    public function test_staff_message_notifies_client_by_email(): void
    {
        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/messages", ['body' => 'Update for you']);

        Mail::assertQueued(NewMessageMail::class, function ($mail) {
            return $mail->hasTo($this->client->email);
        });
    }

    public function test_client_can_send_message_on_their_project(): void
    {
        $this->actingAs($this->client, 'client')
            ->post("/client/projects/{$this->project->id}/messages", ['body' => 'Question here'])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'project_id' => $this->project->id,
            'sender_id'  => $this->client->id,
            'body'       => 'Question here',
        ]);
    }

    public function test_client_message_notifies_owner_by_email(): void
    {
        $this->actingAs($this->client, 'client')
            ->post("/client/projects/{$this->project->id}/messages", ['body' => 'Need help']);

        Mail::assertQueued(NewMessageMail::class, function ($mail) {
            return $mail->hasTo($this->owner->email);
        });
    }

    public function test_client_cannot_send_message_on_another_clients_project(): void
    {
        $otherClient = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Other Client',
            'email'                  => 'other@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);
        $otherProject = Project::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'client_id'   => $otherClient->id,
            'title'       => 'Other Project',
            'status'      => 'active',
        ]);

        $this->actingAs($this->client, 'client')
            ->post("/client/projects/{$otherProject->id}/messages", ['body' => 'Sneaky'])
            ->assertStatus(403);

        Mail::assertNothingQueued();
    }

    public function test_message_body_is_required(): void
    {
        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/messages", ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_message_body_max_length_enforced(): void
    {
        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/messages", ['body' => str_repeat('x', 5001)])
            ->assertSessionHasErrors('body');
    }

    public function test_staff_from_other_business_cannot_message_on_project(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Agency']);
        $otherOwner    = User::create([
            'business_id' => $otherBusiness->id,
            'name'        => 'Other',
            'email'       => 'other-owner@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $otherBusiness->update(['owner_id' => $otherOwner->id]);

        $response = $this->actingAs($otherOwner)
            ->post("/projects/{$this->project->id}/messages", ['body' => 'Cross-tenant']);

        // BusinessScope hides the project (404) or the controller aborts (403) — never a success
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }
}
