<x-client-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Invoices</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if($invoices->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500">
                    No invoices yet.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('client.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4"><x-invoice-status-badge :status="$invoice->status" /></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">₦{{ number_format($invoice->total, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->due_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('client.invoices.pdf', $invoice) }}" class="text-sm text-gray-500 hover:text-gray-700">PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-client-layout>
