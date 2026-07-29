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
                @foreach ($packages as $pkg)
                    <option value="{{ $pkg->id }}" data-has-segments="{{ $pkg->has_segments ? '1' : '0' }}" @selected(old('question_package_id', $user->question_package_id) == $pkg->id)>{{ $pkg->name }} ({{ \App\Models\QuestionPackage::typeLabel($pkg->type) }})</option>
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
            <option value="admin_she" @selected(old('role', $user->role) == 'admin_she')>Admin SHE</option>
            <option value="admin_hr" @selected(old('role', $user->role) == 'admin_hr')>Admin HR</option>
            @if (Auth::user()->isSuperAdmin())
                <option value="super_admin" @selected(old('role', $user->role) == 'super_admin')>Super Admin</option>
            @endif
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    {{-- Segment Config --}}
    <div id="segment-config-section" class="hidden rounded-md border border-amber-200 bg-amber-50 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-800">Konfigurasi Segment</p>
                <p class="text-xs text-amber-600 mt-0.5">Atur waktu pengerjaan per tipe soal. Urutan: PG - Essay - Upload.</p>
            </div>
            <button type="button" id="add-segment-btn" class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">+ Tambah</button>
        </div>
        <div id="segment-rows" class="mt-3 space-y-2">
            @php
                $oldSegs = old('segment_config', $user->segment_config ?? config('assessment.she_default_segments', [['type' => 'multiple_choice', 'duration' => 30]]));
                if (! is_array($oldSegs)) { $oldSegs = [['type' => 'multiple_choice', 'duration' => 30]]; }
            @endphp
            @foreach ($oldSegs as $idx => $seg)
                <div class="segment-row flex items-center gap-2">
                    <select name="segment_config[{{ $idx }}][type]" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1">
                        <option value="multiple_choice" @selected(($seg['type'] ?? '') === 'multiple_choice')">PG (Multiple Choice)</option>
                        <option value="essay" @selected(($seg['type'] ?? '') === 'essay')">Essay</option>
                        <option value="upload" @selected(($seg['type'] ?? '') === 'upload')">Upload File</option>
                    </select>
                    <input type="number" name="segment_config[{{ $idx }}][duration]" value="{{ $seg['duration'] ?? 30 }}" min="1" max="480" placeholder="Menit" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <span class="text-xs text-gray-500">mnt</span>
                    <button type="button" class="remove-segment-btn text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-amber-600">Total waktu: <span id="segment-total">0</span> menit</p>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const packageSelect = document.getElementById('question_package_id');
    const segmentSection = document.getElementById('segment-config-section');
    const segmentRows = document.getElementById('segment-rows');
    const addBtn = document.getElementById('add-segment-btn');
    const totalSpan = document.getElementById('segment-total');

    packageSelect.addEventListener('change', toggleSegmentSection);
    toggleSegmentSection();
    updateTotal();

    function toggleSegmentSection() {
        const selectedOption = packageSelect.querySelector('option:checked');
        const hasSegments = selectedOption && selectedOption.dataset.hasSegments === '1';
        segmentSection.classList.toggle('hidden', !hasSegments);
    }

    function updateTotal() {
        let total = 0;
        segmentRows.querySelectorAll('input[type="number"]').forEach(function (input) {
            total += parseInt(input.value) || 0;
        });
        totalSpan.textContent = total;
    }

    let rowIndex = segmentRows.querySelectorAll('.segment-row').length;
    addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'segment-row flex items-center gap-2';
        row.innerHTML = `
            <select name="segment_config[${rowIndex}][type]" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1">
                <option value="multiple_choice">PG (Multiple Choice)</option>
                <option value="essay">Essay</option>
                <option value="upload">Upload File</option>
            </select>
            <input type="number" name="segment_config[${rowIndex}][duration]" value="30" min="1" max="480" placeholder="Menit" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <span class="text-xs text-gray-500">mnt</span>
            <button type="button" class="remove-segment-btn text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
        `;
        segmentRows.appendChild(row);
        rowIndex++;
        row.querySelector('input[type="number"]').addEventListener('input', updateTotal);
        row.querySelector('.remove-segment-btn').addEventListener('click', function () {
            row.remove();
            updateTotal();
        });
        updateTotal();
    });

    segmentRows.querySelectorAll('.remove-segment-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.segment-row').remove();
            updateTotal();
        });
    });
    segmentRows.querySelectorAll('input[type="number"]').forEach(function (input) {
        input.addEventListener('input', updateTotal);
    });
});
</script>
