<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $invoice->client->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-invoice-status-badge :status="$invoice->status" />

                <a href="{{ route('invoices.downloadPdf', $invoice) }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                    Download PDF
                </a>

                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Edit</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Invoice card --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">

                {{-- Meta --}}
                <div class="grid grid-cols-3 gap-4 p-6 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Bill To</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $invoice->client->name }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->client->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Issue Date</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $invoice->issue_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Due Date</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $invoice->due_date->format('d M Y') }}</p>
                    </div>
                </div>

                {{-- Line items --}}
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-800">{{ $item->description }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600 text-right">{{ $item->quantity }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600 text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-800 font-medium text-right">₦{{ $item->lineTotal() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Total row --}}
                <div class="flex justify-end px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Total Due</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($invoice->total, 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                @can('send', $invoice)
                    <form method="POST" action="{{ route('invoices.send', $invoice) }}" onsubmit="return confirm('Send this invoice to {{ $invoice->client->name }}? They will receive an email.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Send to Client
                        </button>
                    </form>
                @endcan

                @can('markPaid', $invoice)
                    <form method="POST" action="{{ route('invoices.markPaid', $invoice) }}" onsubmit="return confirm('Mark this invoice as paid?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            Mark as Paid
                        </button>
                    </form>
                @endcan

                @can('delete', $invoice)
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">
                            Delete
                        </button>
                    </form>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
