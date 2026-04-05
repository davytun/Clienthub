<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $client;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Test Agency']);
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
    }

    public function test_owner_can_create_project(): void
    {
        $response = $this->actingAs($this->owner)->post('/projects', [
            'client_id'   => $this->client->id,
            'title'       => 'Website Redesign',
            'description' => 'Full redesign',
            'status'      => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'title'       => 'Website Redesign',
            'business_id' => $this->business->id,
            'client_id'   => $this->client->id,
        ]);
    }

    public function test_client_cannot_create_project(): void
    {
        $this->actingAs($this->client)
            ->post('/projects', [
                'client_id' => $this->client->id,
                'title'     => 'Unauthorised',
                'status'    => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get('/projects')->assertRedirect('/login');
    }

    public function test_project_status_validates_enum_values(): void
    {
        $this->actingAs($this->owner)
            ->post('/projects', [
                'client_id' => $this->client->id,
                'title'     => 'Bad Status',
                'status'    => 'invalid_status',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_client_cannot_be_from_different_business(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Co']);
        $foreignClient = User::create([
            'business_id'            => $otherBusiness->id,
            'name'                   => 'Foreign',
            'email'                  => 'foreign@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->post('/projects', [
                'client_id' => $foreignClient->id,
                'title'     => 'Cross-tenant project',
                'status'    => 'active',
            ])
            ->assertStatus(404); // firstOrFail() throws 404 for foreign client
    }
}
