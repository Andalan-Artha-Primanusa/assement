<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Logo -->
    <div class="flex justify-center mb-8">
        <img src="{{ asset('images/amologo.png') }}" alt="AMO Assessment" class="w-auto h-40">
    </div>

    <div class="mb-8">
        <p class="text-sm font-semibold text-indigo-600 uppercase">Login peserta dan admin</p>
        <h1 class="mt-2 text-3xl font-semibold text-gray-900">Masuk ke assessment</h1>
        <p class="mt-2 text-sm text-gray-600">Gunakan akun yang sudah dibuat oleh admin.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block w-full px-4 py-3 mt-2" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block w-full px-4 py-3 mt-2"
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
            <x-primary-button class="justify-center w-full py-3">
                {{ __('Masuk') }}
            </x-primary-button>
        </div>
    </form>

    <div class="pt-6 mt-7 border-t border-gray-200 text-center">
        <p class="text-xs font-semibold uppercase text-gray-400">Assessment powered by</p>
        <img src="{{ asset('images/andalan-logo.png') }}" alt="Andalan" class="mx-auto mt-2 h-auto w-32">
    </div>
</x-guest-layout>
