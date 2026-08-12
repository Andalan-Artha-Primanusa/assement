<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Logo -->
    <div class="flex justify-center mb-5">
        <img src="{{ asset('images/competra-logo.png') }}" alt="Competra" class="h-auto w-full" style="max-width: 260px;">
    </div>

    <div class="mb-5">
        <p class="text-sm font-semibold text-indigo-600 uppercase">Login peserta dan admin</p>
        <h1 class="mt-1 text-3xl font-semibold text-gray-900">Masuk ke assessment</h1>
        <p class="mt-1 text-sm text-gray-600">Gunakan akun yang sudah dibuat oleh admin.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block w-full px-4 py-2.5 mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block w-full px-4 py-2.5 mt-1.5"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:ring-indigo-500" name="remember">
                <span class="text-sm text-gray-600 ms-2">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div>
            <x-primary-button class="justify-center w-full py-2.5">
                {{ __('Masuk') }}
            </x-primary-button>
        </div>
    </form>

    <div class="pt-3 mt-4 border-t border-gray-200 text-center">
        <p class="text-xs font-semibold uppercase text-gray-400">Assessment powered by</p>
        <img src="{{ asset('images/andalan-logo.png') }}" alt="Andalan" class="mx-auto mt-1.5 h-auto" style="width: 88px;">
    </div>

</x-guest-layout>
