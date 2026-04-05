<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoices</h2>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    + New Invoice
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if($invoices->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No invoices yet. <a href="{{ route('invoices.create') }}" class="underline">Create one.</a>
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $invoice->client->name }}</td>
                                    <td class="px-6 py-4"><x-invoice-status-badge :status="$invoice->status" /></td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">₦{{ number_format($invoice->total, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->due_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('invoices.downloadPdf', $invoice) }}" class="text-gray-500 hover:text-gray-700">PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-4">{{ $invoices->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
