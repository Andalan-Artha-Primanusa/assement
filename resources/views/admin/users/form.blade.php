<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Nama" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="password" :value="$method === 'POST' ? 'Password' : 'Password Baru'" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="$method === 'POST'" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="$method === 'POST'" autocomplete="new-password" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-3">
            <x-input-label for="question_package_id" value="Paket Soal" />
            <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua paket</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}" @selected(old('question_package_id', $user->question_package_id) == $package->id)>{{ $package->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="assessment_access_expires_at" value="Akses Sampai" />
            <input
                id="assessment_access_expires_at"
                name="assessment_access_expires_at"
                type="datetime-local"
                value="{{ old('assessment_access_expires_at', $user->assessment_access_expires_at?->format('Y-m-d\TH:i')) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <p class="mt-1 text-xs text-gray-500">Kosongkan jika tanpa batas.</p>
            <x-input-error :messages="$errors->get('assessment_access_expires_at')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="assessment_duration_hours" value="Durasi (jam)" />
            <input
                id="assessment_duration_hours"
                name="assessment_duration_hours"
                type="number"
                min="0.25"
                max="24"
                step="0.25"
                value="{{ old('assessment_duration_hours', round(($user->assessment_duration_minutes ?? config('assessment.default_duration_minutes')) / 60, 2)) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
            <x-input-error :messages="$errors->get('assessment_duration_hours')" class="mt-2" />
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="max_attempts" value="Maks Percobaan" />
            <input
                id="max_attempts"
                name="max_attempts"
                type="number"
                min="1"
                max="100"
                value="{{ old('max_attempts', $user->max_attempts ?? config('assessment.max_attempts')) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
            <p class="mt-1 text-xs text-gray-500">Berapa kali peserta bisa mengulang assessment.</p>
            <x-input-error :messages="$errors->get('max_attempts')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="role" value="Role" />
        <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="user" @selected(old('role', $user->role) == 'user')>Peserta</option>
            <option value="admin_mekanik" @selected(old('role', $user->role) == 'admin_mekanik')>Admin Mekanik</option>
            <option value="admin_operation" @selected(old('role', $user->role) == 'admin_operation')>Admin Operator</option>
            @if (Auth::user()->isSuperAdmin())
                <option value="super_admin" @selected(old('role', $user->role) == 'super_admin')>Super Admin</option>
            @endif
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>
