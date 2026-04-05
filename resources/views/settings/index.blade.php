<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Business Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6" x-data="{ color: '{{ $business->brand_color }}' }">

                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    {{-- Business name --}}
                    <div>
                        <x-input-label for="name" value="Business Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $business->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    {{-- Brand color --}}
                    <div class="mt-6">
                        <x-input-label value="Brand Color" />
                        <div class="mt-1 flex items-center gap-4">
                            <input type="color" x-model="color" class="h-10 w-16 rounded border border-gray-300 cursor-pointer p-0.5" />
                            <x-text-input name="brand_color" type="text" class="w-36" x-model="color" pattern="^#[0-9A-Fa-f]{6}$" required />
                            {{-- Live preview --}}
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium text-white" :style="'background:' + color">
                                Preview
                            </span>
                        </div>
                        <x-input-error :messages="$errors->get('brand_color')" class="mt-1" />
                    </div>

                    {{-- Logo --}}
                    <div class="mt-6">
                        <x-input-label for="logo" value="Logo" />

                        @if($business->logo_path)
                            <div class="mt-2 mb-3">
                                <p class="text-xs text-gray-500 mb-1">Current logo:</p>
                                <img src="{{ route('settings.logo') }}" alt="Business logo" class="h-16 object-contain rounded border border-gray-200 p-1" />
                            </div>
                        @endif

                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg"
                            class="mt-1 text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300" />
                        <p class="mt-1 text-xs text-gray-400">PNG or JPG, max 2 MB. Displayed in the client portal header.</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <x-primary-button>Save Settings</x-primary-button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</x-app-layout>
