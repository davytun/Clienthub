<?php

namespace Tests\Feature;

use App\Mail\InvoiceSentMail;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $client;
    private Business $business;
    private Invoice $draftInvoice;
    private Invoice $sentInvoice;

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

        $this->draftInvoice = Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $this->client->id,
            'invoice_number' => 'INV-0001',
            'status'         => 'draft',
            'issue_date'     => now(),
            'due_date'       => now()->addDays(30),
            'total'          => 100.00,
        ]);
        InvoiceItem::create([
            'invoice_id'  => $this->draftInvoice->id,
            'description' => 'Service',
            'quantity'    => 1,
            'unit_price'  => 100.00,
        ]);

        $this->sentInvoice = Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $this->client->id,
            'invoice_number' => 'INV-0002',
            'status'         => 'sent',
            'issue_date'     => now(),
            'due_date'       => now()->addDays(30),
            'total'          => 200.00,
        ]);
    }

    public function test_draft_invoice_can_be_edited(): void
    {
        $this->actingAs($this->owner)
            ->get("/invoices/{$this->draftInvoice->id}/edit")
            ->assertOk();
    }

    public function test_sent_invoice_cannot_be_edited(): void
    {
        $this->actingAs($this->owner)
            ->get("/invoices/{$this->sentInvoice->id}/edit")
            ->assertStatus(403);
    }

    public function test_draft_invoice_can_be_deleted(): void
    {
        $this->actingAs($this->owner)
            ->delete("/invoices/{$this->draftInvoice->id}")
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseMissing('invoices', ['id' => $this->draftInvoice->id]);
    }

    public function test_sent_invoice_cannot_be_deleted(): void
    {
        $this->actingAs($this->owner)
            ->delete("/invoices/{$this->sentInvoice->id}")
            ->assertStatus(403);
    }

    public function test_staff_can_mark_sent_invoice_as_paid(): void
    {
        $this->actingAs($this->owner)
            ->post("/invoices/{$this->sentInvoice->id}/paid")
            ->assertRedirect();

        $this->sentInvoice->refresh();
        $this->assertEquals('paid', $this->sentInvoice->status);
    }

    public function test_draft_invoice_cannot_be_marked_paid(): void
    {
        $this->actingAs($this->owner)
            ->post("/invoices/{$this->draftInvoice->id}/paid")
            ->assertStatus(403);
    }

    public function test_client_can_view_sent_invoice(): void
    {
        $this->actingAs($this->client, 'client')
            ->get("/client/invoices/{$this->sentInvoice->id}")
            ->assertOk();
    }

    public function test_client_cannot_view_draft_invoice(): void
    {
        $this->actingAs($this->client, 'client')
            ->get("/client/invoices/{$this->draftInvoice->id}")
            ->assertStatus(403);
    }

    public function test_client_only_sees_their_own_invoices_in_list(): void
    {
        $otherClient = User::create([
            'business_id'            => $this->business->id,
            'name'                   => 'Other Client',
            'email'                  => 'other@example.com',
            'password'               => bcrypt('password'),
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);
        Invoice::withoutGlobalScopes()->create([
            'business_id'    => $this->business->id,
            'client_id'      => $otherClient->id,
            'invoice_number' => 'INV-0003',
            'status'         => 'sent',
            'issue_date'     => now(),
            'due_date'       => now()->addDays(30),
            'total'          => 500.00,
        ]);

        $response = $this->actingAs($this->client, 'client')
            ->get('/client/invoices');

        $response->assertOk();
        $response->assertSee('INV-0002');  // their own sent invoice
        $response->assertDontSee('INV-0003');  // other client's invoice
    }

    public function test_sending_invoice_dispatches_email_to_client(): void
    {
        $this->actingAs($this->owner)
            ->post("/invoices/{$this->draftInvoice->id}/send")
            ->assertRedirect();

        Mail::assertQueued(InvoiceSentMail::class, function ($mail) {
            return $mail->hasTo($this->client->email);
        });

        $this->draftInvoice->refresh();
        $this->assertEquals('sent', $this->draftInvoice->status);
    }

    public function test_already_sent_invoice_cannot_be_re_sent(): void
    {
        $this->actingAs($this->owner)
            ->post("/invoices/{$this->sentInvoice->id}/send")
            ->assertStatus(403);
    }
}
