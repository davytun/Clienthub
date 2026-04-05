<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Team</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Invite form --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Invite a Team Member</h3>
                <form method="POST" action="{{ route('staff.invite') }}" class="flex flex-col sm:flex-row gap-3">
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

            {{-- Team list --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($staff as $member)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $member->name }}
                                    @if($member->id === auth()->id())
                                        <span class="ml-1 text-xs text-gray-400">(you)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $member->email }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $member->isOwner() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst($member->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($member->hasAcceptedInvitation())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Invite pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    @if(! $member->isOwner() && $member->id !== auth()->id())
                                        <form method="POST" action="{{ route('staff.destroy', $member) }}"
                                              onsubmit="return confirm('Remove {{ $member->name }} from the team?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
