<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staff;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->business = Business::create(['name' => 'Agency', 'brand_color' => '#123456']);
        $this->owner    = User::create([
            'business_id' => $this->business->id,
            'name'        => 'Owner',
            'email'       => 'owner@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'owner',
        ]);
        $this->business->update(['owner_id' => $this->owner->id]);

        $this->staff = User::create([
            'business_id' => $this->business->id,
            'name'        => 'Staff',
            'email'       => 'staff@example.com',
            'password'    => bcrypt('password'),
            'role'        => 'staff',
        ]);
    }

    public function test_owner_can_update_business_settings(): void
    {
        $this->actingAs($this->owner)
            ->patch('/settings', [
                'name'        => 'New Agency Name',
                'brand_color' => '#AABBCC',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'id'          => $this->business->id,
            'name'        => 'New Agency Name',
            'brand_color' => '#AABBCC',
        ]);
    }

    public function test_staff_cannot_update_business_settings(): void
    {
        $this->actingAs($this->staff)
            ->patch('/settings', [
                'name'        => 'Hacked Name',
                'brand_color' => '#000000',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('businesses', ['name' => 'Hacked Name']);
    }

    public function test_brand_color_must_be_valid_hex(): void
    {
        $this->actingAs($this->owner)
            ->patch('/settings', [
                'name'        => 'Agency',
                'brand_color' => 'red',  // invalid
            ])
            ->assertSessionHasErrors('brand_color');
    }

    public function test_owner_can_upload_logo(): void
    {
        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($this->owner)
            ->patch('/settings', [
                'name'        => 'Agency',
                'brand_color' => '#123456',
                'logo'        => $logo,
            ])
            ->assertRedirect();

        $this->business->refresh();
        $this->assertNotNull($this->business->logo_path);
        Storage::assertExists($this->business->logo_path);
    }

    public function test_uploading_new_logo_deletes_old_one(): void
    {
        // Set initial logo
        $oldPath = "logos/{$this->business->id}.jpg";
        Storage::put($oldPath, 'old logo content');
        $this->business->update(['logo_path' => $oldPath]);

        $newLogo = UploadedFile::fake()->image('new-logo.png', 100, 100);

        $this->actingAs($this->owner)
            ->patch('/settings', [
                'name'        => 'Agency',
                'brand_color' => '#123456',
                'logo'        => $newLogo,
            ]);

        Storage::assertMissing($oldPath);
    }

    public function test_logo_upload_rejects_non_image(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->owner)
            ->patch('/settings', [
                'name'        => 'Agency',
                'brand_color' => '#123456',
                'logo'        => $file,
            ])
            ->assertSessionHasErrors('logo');
    }
}
