<x-client-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('client.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; All Invoices</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">{{ $invoice->invoice_number }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <x-invoice-status-badge :status="$invoice->status" />
                <a href="{{ route('client.invoices.pdf', $invoice) }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">

                <div class="grid grid-cols-2 gap-4 p-6 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">From</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $invoice->business->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Due Date</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $invoice->due_date->format('d M Y') }}</p>
                    </div>
                </div>

                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/2">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-800">{{ $item->description }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600 text-right">{{ $item->quantity }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600 text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-800 text-right">₦{{ $item->lineTotal() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="flex justify-end px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Total Due</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($invoice->total, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
