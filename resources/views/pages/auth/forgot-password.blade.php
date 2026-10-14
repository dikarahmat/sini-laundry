<x-layouts::auth :title="__('Lupa Kata Sandi')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Lupa Kata Sandi?')" :description="__('Masukkan email kamu untuk menerima tautan atur ulang kata sandi')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email')"
                type="email"
                required
                autofocus
                placeholder="email@contoh.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Kirim Tautan Atur Ulang') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-ink-600">
            <span>{{ __('Atau, kembali ke') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('halaman masuk') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
