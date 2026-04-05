<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    private function client()
    {
        return auth()->guard('client')->user();
    }

    public function index(): View
    {
        $invoices = Invoice::where('client_id', $this->client()->id)
            ->whereIn('status', ['sent', 'paid']) // clients only see non-draft invoices
            ->latest()
            ->get();

        return view('client.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        abort_unless($invoice->client_id === $this->client()->id, 403);
        abort_unless($invoice->status !== 'draft', 403);

        $invoice->load(['items', 'business']);

        return view('client.invoices.show', compact('invoice'));
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        abort_unless($invoice->client_id === $this->client()->id, 403);
        abort_unless($invoice->status !== 'draft', 403);

        $invoice->load(['items', 'business', 'client']);

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
