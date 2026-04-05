<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }

        .page { padding: 48px; max-width: 800px; margin: 0 auto; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 40px; }
        .header-left  { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }

        .business-name {
            font-size: 22px;
            font-weight: bold;
            color: {{ $invoice->business->brand_color }};
            margin-bottom: 4px;
        }

        .invoice-label {
            font-size: 28px;
            font-weight: bold;
            color: #111;
            letter-spacing: -0.5px;
        }
        .invoice-number {
            font-size: 15px;
            color: #555;
            margin-top: 2px;
        }

        /* Status badge */
        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .status-draft    { background: #f3f4f6; color: #6b7280; }
        .status-sent     { background: #dbeafe; color: #1d4ed8; }
        .status-paid     { background: #d1fae5; color: #065f46; }

        /* Meta table */
        .meta { display: table; width: 100%; margin-bottom: 36px; }
        .meta-block { display: table-cell; width: 33%; vertical-align: top; }
        .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; }
        .meta-value { font-size: 13px; color: #111; font-weight: 500; }

        /* Divider */
        .divider { border: none; border-top: 2px solid {{ $invoice->business->brand_color }}; margin-bottom: 24px; }
        .divider-light { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }

        /* Items table */
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead tr {
            background: {{ $invoice->business->brand_color }};
            color: #fff;
        }
        table.items thead th {
            padding: 10px 14px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.items thead th.right { text-align: right; }
        table.items tbody tr { border-bottom: 1px solid #f3f4f6; }
        table.items tbody td { padding: 10px 14px; vertical-align: top; font-size: 13px; }
        table.items tbody td.right { text-align: right; }
        table.items tbody tr:nth-child(even) { background: #f9fafb; }

        /* Total */
        .total-section { margin-top: 8px; }
        .total-row { display: table; width: 100%; }
        .total-spacer { display: table-cell; width: 60%; }
        .total-box { display: table-cell; width: 40%; }
        .total-line { display: table; width: 100%; padding: 6px 14px; }
        .total-line-label { display: table-cell; font-size: 13px; color: #6b7280; }
        .total-line-value { display: table-cell; text-align: right; font-size: 13px; color: #111; }
        .grand-total {
            background: {{ $invoice->business->brand_color }};
            color: #fff;
            border-radius: 4px;
            margin-top: 4px;
        }
        .grand-total .total-line-label,
        .grand-total .total-line-value {
            color: #fff;
            font-weight: bold;
            font-size: 15px;
        }

        /* Footer */
        .footer { margin-top: 48px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="business-name">{{ $invoice->business->name }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-label">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div>
                <span class="status status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>
    </div>

    <hr class="divider" />

    {{-- Meta: Bill To / Dates --}}
    <div class="meta">
        <div class="meta-block">
            <div class="meta-label">Bill To</div>
            <div class="meta-value">{{ $invoice->client->name }}</div>
            <div style="color:#6b7280; font-size:12px; margin-top:2px;">{{ $invoice->client->email }}</div>
        </div>
        <div class="meta-block">
            <div class="meta-label">Issue Date</div>
            <div class="meta-value">{{ $invoice->issue_date->format('d M Y') }}</div>
        </div>
        <div class="meta-block">
            <div class="meta-label">Due Date</div>
            <div class="meta-value">{{ $invoice->due_date->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Line Items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:50%">Description</th>
                <th class="right" style="width:15%">Qty</th>
                <th class="right" style="width:20%">Unit Price</th>
                <th class="right" style="width:15%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">₦{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">₦{{ $item->lineTotal() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total --}}
    <div class="total-section">
        <div class="total-row">
            <div class="total-spacer"></div>
            <div class="total-box">
                <div class="grand-total">
                    <div class="total-line">
                        <div class="total-line-label">Total Due</div>
                        <div class="total-line-value">₦{{ number_format($invoice->total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Generated by {{ $invoice->business->name }} &middot; {{ $invoice->invoice_number }} &middot; {{ now()->format('d M Y') }}
    </div>

</div>
</body>
</html>
