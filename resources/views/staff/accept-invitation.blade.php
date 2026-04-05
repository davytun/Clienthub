<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Hi <strong>{{ $member->name }}</strong>, set a password to activate your staff account.
    </div>

    <form method="POST" action="{{ route('staff.invitation.activate', ['token' => $token]) }}?{{ http_build_query(request()->only('expires', 'signature')) }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Activate Account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
