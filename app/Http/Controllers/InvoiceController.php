<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceSentMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::with('client')
            ->latest()
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $clients = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        return view('invoices.create', compact('clients'));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $client = User::where('id', $request->client_id)
            ->where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->firstOrFail();

        $invoice = DB::transaction(function () use ($request, $client) {
            $invoiceNumber = $this->generateInvoiceNumber(auth()->user()->business_id);

            $invoice = Invoice::create([
                'business_id'    => auth()->user()->business_id,
                'client_id'      => $client->id,
                'invoice_number' => $invoiceNumber,
                'status'         => 'draft',
                'issue_date'     => $request->issue_date,
                'due_date'       => $request->due_date,
                'total'          => 0,
            ]);

            $total = $this->syncItems($invoice, $request->items);
            $invoice->update(['total' => $total]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'business']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $clients = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        $invoice->load('items');

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $client = User::where('id', $request->client_id)
            ->where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->firstOrFail();

        DB::transaction(function () use ($request, $invoice, $client) {
            $invoice->update([
                'client_id'  => $client->id,
                'issue_date' => $request->issue_date,
                'due_date'   => $request->due_date,
            ]);

            $total = $this->syncItems($invoice, $request->items);
            $invoice->update(['total' => $total]);
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    /**
     * Mark invoice as sent and email the client.
     */
    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $invoice->update(['status' => 'sent']);

        $invoice->load(['client', 'business', 'items']);
        Mail::to($invoice->client->email)->queue(new InvoiceSentMail($invoice));

        return back()->with('success', "Invoice {$invoice->invoice_number} sent to {$invoice->client->name}.");
    }

    /**
     * Mark a sent invoice as paid.
     */
    public function markPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $invoice->update(['status' => 'paid']);

        return back()->with('success', 'Invoice marked as paid.');
    }

    /**
     * Stream the invoice as a PDF download.
     */
    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('downloadPdf', $invoice);

        $invoice->load(['client', 'items', 'business']);

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Generate the next sequential invoice number for a business.
     * Must be called inside a DB transaction with a lock to prevent duplicates.
     */
    private function generateInvoiceNumber(int $businessId): string
    {
        $latest = Invoice::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($latest) {
            // Parse the numeric suffix: "INV-0012" → 12 → 13
            $next = (int) substr($latest, 4) + 1;
        } else {
            $next = 1;
        }

        return 'INV-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Delete existing items for an invoice and replace them.
     * Returns the calculated total.
     */
    private function syncItems(Invoice $invoice, array $items): float
    {
        $invoice->items()->delete();

        $total = 0;

        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'quantity'    => (int) $item['quantity'],
                'unit_price'  => (float) $item['unit_price'],
            ]);
            $total += (int) $item['quantity'] * (float) $item['unit_price'];
        }

        return $total;
    }
}
