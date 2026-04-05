<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit {{ $invoice->invoice_number }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('invoices.update', $invoice) }}" x-data="invoiceForm()">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $invoice->client_id) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="issue_date" value="Issue Date" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', $invoice->issue_date->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('issue_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Due Date" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', $invoice->due_date->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Line Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="text-xs text-gray-500 uppercase tracking-wider">
                                        <th class="text-left pb-2 pr-3" style="width:50%">Description</th>
                                        <th class="text-right pb-2 px-3" style="width:12%">Qty</th>
                                        <th class="text-right pb-2 px-3" style="width:22%">Unit Price</th>
                                        <th class="text-right pb-2 px-3" style="width:12%">Total</th>
                                        <th class="pb-2" style="width:4%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="border-t border-gray-100">
                                            <td class="py-2 pr-3">
                                                <input type="text" :name="`items[${index}][description]`" x-model="item.description"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" required />
                                            </td>
                                            <td class="py-2 px-3">
                                                <input type="number" :name="`items[${index}][quantity]`" x-model.number="item.quantity" min="1"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right focus:ring-indigo-500 focus:border-indigo-500" required />
                                            </td>
                                            <td class="py-2 px-3">
                                                <input type="number" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" min="0" step="0.01"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right focus:ring-indigo-500 focus:border-indigo-500" required />
                                            </td>
                                            <td class="py-2 px-3 text-right text-sm text-gray-700" x-text="formatMoney(item.quantity * item.unit_price)"></td>
                                            <td class="py-2 pl-2">
                                                <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" @click="addItem()" class="mt-3 text-sm text-indigo-600 hover:text-indigo-900 font-medium">+ Add line item</button>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Total</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(total())"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-4 border-t border-gray-100 pt-6">
                        <x-primary-button>Save Changes</x-primary-button>
                        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $initialItems = old('items', $invoice->items->map(fn ($i) => [
            'description' => $i->description,
            'quantity'    => $i->quantity,
            'unit_price'  => (float) $i->unit_price,
        ])->values()->toArray());
    @endphp
    <script>
        function invoiceForm() {
            return {
                items: @json($initialItems),
                addItem()  { this.items.push({ description: '', quantity: 1, unit_price: 0 }); },
                removeItem(i) { this.items.splice(i, 1); },
                total()    { return this.items.reduce((s, i) => s + (i.quantity * i.unit_price), 0); },
                formatMoney(v) { return '₦' + Number(v).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}); },
            }
        }
    </script>
    @endpush
</x-app-layout>
