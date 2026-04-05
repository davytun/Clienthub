<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clients</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Invite form --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Invite a Client</h3>
                <form method="POST" action="{{ route('clients.invite') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-text-input name="name" type="text" placeholder="Full name" class="block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="flex-1">
                        <x-text-input name="email" type="email" placeholder="Email address" class="block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <x-primary-button type="submit">Send Invite</x-primary-button>
                </form>
            </div>

            {{-- Client list --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if($clients->isEmpty())
                    <div class="p-8 text-center text-gray-500">No clients yet. Use the form above to invite your first client.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($clients as $client)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $client->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $client->email }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($client->hasAcceptedInvitation())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Invite pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-400">
                                        {{ $client->invitation_accepted_at?->format('d M Y') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
