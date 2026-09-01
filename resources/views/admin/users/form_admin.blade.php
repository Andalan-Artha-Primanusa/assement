<form method="POST" action="{{ $action }}" class="space-y-8" data-confirm
      data-confirm-title="{{ $method === 'POST' ? 'Tambah Admin?' : 'Simpan perubahan Admin?' }}"
      data-confirm-message="Pastikan data yang diinput sudah benar."
      data-confirm-text="{{ $method === 'POST' ? 'Ya, tambah admin' : 'Ya, simpan admin' }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-8">
        {{-- CARD 1: INFORMASI AKUN (LOGIN) & ROLE --}}
        <div class="rounded-xl border border-indigo-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-indigo-100 bg-indigo-50/50 px-6 py-4 flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </div>
                <h3 class="text-base font-semibold text-indigo-900">Informasi Akun Admin</h3>
            </div>
            <div class="p-6 space-y-6">
                {{-- ROLE ADMIN SELECTION --}}
                <div>
                    <x-input-label for="role" value="Pilih Hak Akses Admin" class="mb-3 text-sm font-semibold text-gray-700" />
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        @php
                            $adminRoles = [
                                'admin_mekanik' => ['label' => 'Admin Mekanik', 'color' => 'indigo'],
                                'admin_operation' => ['label' => 'Admin Operator', 'color' => 'purple'],
                                'admin_she' => ['label' => 'Admin SHE', 'color' => 'cyan'],
                                'admin_hr' => ['label' => 'Admin HR', 'color' => 'rose'],
                            ];
                            if (Auth::user()->isSuperAdmin()) {
                                $adminRoles['super_admin'] = ['label' => 'Super Admin', 'color' => 'red'];
                            }
                        @endphp

                        @foreach($adminRoles as $val => $data)
                            <label class="relative flex cursor-pointer flex-col rounded-lg border border-gray-300 p-3 shadow-sm focus:outline-none hover:bg-gray-50 has-[:checked]:border-{{ $data['color'] }}-600 has-[:checked]:bg-{{ $data['color'] }}-50 has-[:checked]:ring-1 has-[:checked]:ring-{{ $data['color'] }}-600 transition-all">
                                <input type="radio" name="role" value="{{ $val }}" class="sr-only" @checked(old('role', $user->role ?? 'admin_hr') === $val)>
                                <span class="block text-sm font-semibold text-gray-900 text-center">{{ $data['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Nama Lengkap" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" :value="old('name', $user->name ?? '')" required placeholder="Contoh: HR Manager" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Alamat Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" :value="old('email', $user->email ?? '')" required placeholder="Contoh: hrd@example.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 border border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-900">Pengaturan Password</h4>
                        <button type="button" onclick="generatePassword()" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-100 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-200 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Generate Password
                        </button>
                    </div>
                    
                    <div class="grid gap-6 sm:grid-cols-2 mt-4">
                        <div>
                            <x-input-label for="password" :value="$method === 'POST' ? 'Password' : 'Password Baru (Opsional)'" />
                            <div class="relative mt-1">
                                <x-text-input id="password" name="password" type="password" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10" :required="$method === 'POST'" autocomplete="new-password" placeholder="Minimal 8 karakter" />
                                <button type="button" onclick="togglePasswordVisibility('password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                            <div class="relative mt-1">
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10" :required="$method === 'POST'" autocomplete="new-password" placeholder="Ketik ulang password" />
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: LOKASI --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Pembatasan Lokasi Akses</h3>
            </div>
            <div class="p-6">
                <div class="max-w-xl">
                    <x-input-label for="site" value="Site (Lokasi)" />
                    <select id="site" name="site" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        <option value="">-- Pilih Site --</option>
                        @foreach (($allSites ?? collect()) as $s)
                            <option value="{{ $s->code }}" @selected(old('site', $user->site ?? '') === $s->code)>
                                {{ $s->code }} — {{ $s->name }}{{ $s->code === 'HO' ? ' (Akses Semua Site)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Pilih <strong>HO</strong> untuk memberikan admin ini hak akses ke semua data Site.</p>
                    <x-input-error :messages="$errors->get('site')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">Batal</a>
            <button class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow transition-all">{{ $button }}</button>
        </div>
    </div>
</form>

<script>
    function generatePassword() {
        const length = 10;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let retVal = "";
        for (let i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        
        const pwdInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        
        pwdInput.value = retVal;
        confirmInput.value = retVal;
        
        // Make passwords visible so admin can copy/see them
        pwdInput.type = "text";
        confirmInput.type = "text";
        
        alert("Password berhasil di-generate: " + retVal + "\n\nSilakan simpan password ini untuk diinfokan ke Admin terkait.");
    }
    
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }
</script>
