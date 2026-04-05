<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\File;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $client;
    private Business $business;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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

    public function test_staff_can_upload_file_to_project(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/files", ['file' => $file]);

        $response->assertRedirect();
        $this->assertDatabaseHas('files', [
            'project_id'  => $this->project->id,
            'business_id' => $this->business->id,
            'uploaded_by' => $this->owner->id,
        ]);
    }

    public function test_uploaded_filename_is_sanitized(): void
    {
        $file = UploadedFile::fake()->create('../../../etc/passwd.pdf', 100, 'application/pdf');

        $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/files", ['file' => $file]);

        $record = File::withoutGlobalScopes()->latest()->first();
        $this->assertNotNull($record);
        $this->assertStringNotContainsString('/', $record->original_name);
        $this->assertStringNotContainsString('..', $record->original_name);
    }

    public function test_file_upload_rejects_oversized_files(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 11_000, 'application/pdf'); // 11 MB > 10 MB limit

        $response = $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/files", ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_file_upload_rejects_disallowed_types(): void
    {
        $file = UploadedFile::fake()->create('script.php', 10, 'text/x-php');

        $response = $this->actingAs($this->owner)
            ->post("/projects/{$this->project->id}/files", ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_staff_can_download_file(): void
    {
        $path = 'files/1/1/test.pdf';
        Storage::put($path, 'PDF content');

        $fileRecord = File::withoutGlobalScopes()->create([
            'project_id'    => $this->project->id,
            'business_id'   => $this->business->id,
            'uploaded_by'   => $this->owner->id,
            'original_name' => 'test.pdf',
            'path'          => $path,
            'size_bytes'    => 11,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("/files/{$fileRecord->id}/download");

        $response->assertOk();
    }

    public function test_staff_from_another_business_cannot_download_file(): void
    {
        $otherBusiness = Business::create(['name' => 'Other']);
        $otherOwner    = User::create([
            'business_id' => $otherBusiness->id,
            'name'        => 'Other Owner',
            'email'       => 'other@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $otherBusiness->update(['owner_id' => $otherOwner->id]);

        $path = 'files/1/1/secret.pdf';
        Storage::put($path, 'secret');

        $fileRecord = File::withoutGlobalScopes()->create([
            'project_id'    => $this->project->id,
            'business_id'   => $this->business->id,
            'uploaded_by'   => $this->owner->id,
            'original_name' => 'secret.pdf',
            'path'          => $path,
            'size_bytes'    => 6,
        ]);

        $response = $this->actingAs($otherOwner)
            ->get("/files/{$fileRecord->id}/download");

        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }

    public function test_owner_can_delete_file(): void
    {
        $path = 'files/1/1/delete-me.pdf';
        Storage::put($path, 'data');

        $fileRecord = File::withoutGlobalScopes()->create([
            'project_id'    => $this->project->id,
            'business_id'   => $this->business->id,
            'uploaded_by'   => $this->owner->id,
            'original_name' => 'delete-me.pdf',
            'path'          => $path,
            'size_bytes'    => 4,
        ]);

        $this->actingAs($this->owner)
            ->delete("/files/{$fileRecord->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $fileRecord->id]);
        Storage::assertMissing($path);
    }

    public function test_client_can_download_their_project_file(): void
    {
        $path = 'files/1/1/client-file.pdf';
        Storage::put($path, 'client data');

        $fileRecord = File::withoutGlobalScopes()->create([
            'project_id'    => $this->project->id,
            'business_id'   => $this->business->id,
            'uploaded_by'   => $this->owner->id,
            'original_name' => 'client-file.pdf',
            'path'          => $path,
            'size_bytes'    => 11,
        ]);

        $response = $this->actingAs($this->client, 'client')
            ->get("/client/projects/{$this->project->id}/files/{$fileRecord->id}/download");

        $response->assertOk();
    }

    public function test_client_cannot_download_file_from_another_clients_project(): void
    {
        $otherClient = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Other Client',
            'email'                  => 'other-client@example.com',
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

        $path = 'files/1/2/other.pdf';
        Storage::put($path, 'other data');

        $fileRecord = File::withoutGlobalScopes()->create([
            'project_id'    => $otherProject->id,
            'business_id'   => $this->business->id,
            'uploaded_by'   => $this->owner->id,
            'original_name' => 'other.pdf',
            'path'          => $path,
            'size_bytes'    => 10,
        ]);

        $response = $this->actingAs($this->client, 'client')
            ->get("/client/projects/{$otherProject->id}/files/{$fileRecord->id}/download");

        $response->assertStatus(403);
    }
}
