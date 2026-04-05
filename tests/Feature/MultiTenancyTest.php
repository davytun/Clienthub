<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that the BusinessScope global scope prevents cross-tenant data access.
 * These are the most important tests in the project — a failure here means data leaks.
 */
class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessWithOwner(string $businessName, string $email): array
    {
        $business = Business::create(['name' => $businessName]);
        $owner    = User::create([
            'business_id' => $business->id,
            'name'        => 'Owner',
            'email'       => $email,
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $business->update(['owner_id' => $owner->id]);

        return [$business, $owner];
    }

    private function createClient(Business $business): User
    {
        return User::create([
            'business_id'            => $business->id,
            'name'                   => 'Client',
            'email'                  => "client-{$business->id}@example.com",
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);
    }

    public function test_staff_cannot_see_other_business_projects(): void
    {
        [$businessA, $ownerA] = $this->createBusinessWithOwner('Business A', 'a@example.com');
        [$businessB, $ownerB] = $this->createBusinessWithOwner('Business B', 'b@example.com');

        $clientA = $this->createClient($businessA);
        $clientB = $this->createClient($businessB);

        // Project belongs to Business B
        $projectB = Project::withoutGlobalScopes()->create([
            'business_id' => $businessB->id,
            'client_id'   => $clientB->id,
            'title'       => 'Business B Secret Project',
            'status'      => 'active',
        ]);

        // Owner A tries to access Business B's project directly
        $response = $this->actingAs($ownerA)->get("/projects/{$projectB->id}");

        // Must get 403 (policy) or 404 (scope makes it invisible) — never 200
        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 404]),
            "Expected 403 or 404 but got {$response->getStatusCode()} — cross-tenant access!"
        );
    }

    public function test_global_scope_filters_projects_by_business(): void
    {
        [$businessA, $ownerA] = $this->createBusinessWithOwner('Business A', 'a@example.com');
        [$businessB, $ownerB] = $this->createBusinessWithOwner('Business B', 'b@example.com');

        $clientA = $this->createClient($businessA);
        $clientB = $this->createClient($businessB);

        // Create projects for both businesses
        Project::withoutGlobalScopes()->create([
            'business_id' => $businessA->id,
            'client_id'   => $clientA->id,
            'title'       => 'Project A',
            'status'      => 'active',
        ]);
        Project::withoutGlobalScopes()->create([
            'business_id' => $businessB->id,
            'client_id'   => $clientB->id,
            'title'       => 'Project B',
            'status'      => 'active',
        ]);

        // When authenticated as Business A, only Business A's projects should be visible
        $this->actingAs($ownerA);
        $projects = Project::all();

        $this->assertCount(1, $projects);
        $this->assertEquals('Project A', $projects->first()->title);
    }

    public function test_client_cannot_see_another_clients_project(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Agency', 'agency@example.com');

        $client1 = $this->createClient($business);
        $client2 = User::create([
            'business_id'            => $business->id,
            'name'                   => 'Other Client',
            'email'                  => 'other@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);

        $projectForClient2 = Project::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'client_id'   => $client2->id,
            'title'       => 'Client 2 Private Project',
            'status'      => 'active',
        ]);

        // Client 1 tries to access Client 2's project
        $response = $this->actingAs($client1, 'client')
            ->get("/client/projects/{$projectForClient2->id}");

        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 404]),
            "Client accessed another client's project — isolation failure!"
        );
    }
}
