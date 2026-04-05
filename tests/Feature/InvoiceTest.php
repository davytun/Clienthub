<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    public function test_invoice_number_is_auto_generated(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'client_id'  => $this->client->id,
            'issue_date' => '2026-01-01',
            'due_date'   => '2026-01-15',
            'items'      => [
                ['description' => 'Design work', 'quantity' => 2, 'unit_price' => 50000],
            ],
        ]);

        $invoice = Invoice::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^INV-\d{4}$/', $invoice->invoice_number);
    }

    public function test_sequential_invoice_numbers_do_not_collide(): void
    {
        $this->actingAs($this->owner);

        $this->post('/invoices', [
            'client_id'  => $this->client->id,
            'issue_date' => '2026-01-01',
            'due_date'   => '2026-01-15',
            'items'      => [['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->post('/invoices', [
            'client_id'  => $this->client->id,
            'issue_date' => '2026-01-01',
            'due_date'   => '2026-01-15',
            'items'      => [['description' => 'Item 2', 'quantity' => 1, 'unit_price' => 200]],
        ]);

        $numbers = Invoice::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->pluck('invoice_number')
            ->toArray();

        $this->assertCount(2, array_unique($numbers), 'Duplicate invoice numbers generated!');
        $this->assertContains('INV-0001', $numbers);
        $this->assertContains('INV-0002', $numbers);
    }

    public function test_invoice_total_is_calculated_from_line_items(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'client_id'  => $this->client->id,
            'issue_date' => '2026-01-01',
            'due_date'   => '2026-01-15',
            'items'      => [
                ['description' => 'Design',   'quantity' => 3, 'unit_price' => 10000],
                ['description' => 'Revisions', 'quantity' => 2, 'unit_price' => 5000],
            ],
        ]);

        $invoice = Invoice::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->first();

        $this->assertEquals(40000, $invoice->total); // 3×10000 + 2×5000
    }

    public function test_sending_invoice_dispatches_email(): void
    {
        Mail::fake();

        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $this->client->id,
            'invoice_number' => 'INV-0001',
            'status'         => 'draft',
            'issue_date'     => '2026-01-01',
            'due_date'       => '2026-01-15',
            'total'          => 50000,
        ]);

        $this->actingAs($this->owner)
            ->post("/invoices/{$invoice->id}/send")
            ->assertRedirect();

        Mail::assertQueued(\App\Mail\InvoiceSentMail::class, function ($mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });

        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
    }

    public function test_sent_invoice_cannot_be_edited(): void
    {
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $this->client->id,
            'invoice_number' => 'INV-0001',
            'status'         => 'sent',
            'issue_date'     => '2026-01-01',
            'due_date'       => '2026-01-15',
            'total'          => 50000,
        ]);

        $this->actingAs($this->owner)
            ->get("/invoices/{$invoice->id}/edit")
            ->assertStatus(403);
    }

    public function test_client_cannot_see_draft_invoice(): void
    {
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $this->client->id,
            'invoice_number' => 'INV-0001',
            'status'         => 'draft',
            'issue_date'     => '2026-01-01',
            'due_date'       => '2026-01-15',
            'total'          => 50000,
        ]);

        $this->actingAs($this->client, 'client')
            ->get("/client/invoices/{$invoice->id}")
            ->assertStatus(403);
    }
}
