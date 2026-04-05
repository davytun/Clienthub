<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Hi {{ $invoice->client->name }},

You have a new invoice from **{{ $invoice->business->name }}**.

| | |
|---|---|
| **Invoice number** | {{ $invoice->invoice_number }} |
| **Amount due** | ₦{{ number_format($invoice->total, 2) }} |
| **Due date** | {{ $invoice->due_date->format('d M Y') }} |

Log in to your portal to view the full invoice and download a PDF copy.

<x-mail::button :url="route('client.invoices.show', $invoice)">
View Invoice
</x-mail::button>

Thanks,<br>
{{ $invoice->business->name }}
</x-mail::message>
