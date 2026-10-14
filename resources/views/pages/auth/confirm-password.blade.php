<x-layouts::auth :title="__('Konfirmasi Kata Sandi')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Konfirmasi Kata Sandi')"
            :description="__('Ini adalah area aman. Konfirmasi kata sandi kamu sebelum melanjutkan.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        {{-- @chisel-passkeys --}}
        @if (false)
            <x-passkey-verify
                options-route="passkey.confirm-options"
                submit-route="passkey.confirm"
                :label="__('Konfirmasi dengan passkey')"
                :loading-label="__('Mengonfirmasi...')"
                :separator="__('Atau konfirmasi dengan kata sandi')"
            />
        @endif
        {{-- @end-chisel-passkeys --}}

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Kata Sandi')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Kata Sandi')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Konfirmasi') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
