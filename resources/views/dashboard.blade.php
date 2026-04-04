<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard — {{ auth()->user()->business->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Clients</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['clients'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Active Projects</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['active_projects'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Unpaid Invoices</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['unpaid_invoices'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Total Invoiced</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">₦{{ number_format($stats['total_invoiced'], 2) }}</p>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
