<form method="POST" action="{{ $action }}" class="space-y-8" data-confirm
      data-confirm-title="{{ $method === 'POST' ? 'Tambah Peserta?' : 'Simpan perubahan Peserta?' }}"
      data-confirm-message="Pastikan data yang diinput sudah benar."
      data-confirm-text="{{ $method === 'POST' ? 'Ya, tambah peserta' : 'Ya, simpan peserta' }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Hidden input to fix the role as user --}}
    <input type="hidden" name="role" value="user">

    <div class="space-y-8">
        {{-- CARD 1: INFORMASI AKUN (LOGIN) --}}
        <div class="rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-4 flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <h3 class="text-base font-semibold text-emerald-900">Informasi Akun Peserta</h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Nama Lengkap" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" :value="old('name', $user->name ?? '')" required placeholder="Contoh: Budi Santoso" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Alamat Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" :value="old('email', $user->email ?? '')" required placeholder="Contoh: budi@example.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 rounded-lg bg-gray-50 p-4 border border-gray-100">
                    <div>
                        <x-input-label for="password" :value="$method === 'POST' ? 'Password' : 'Password Baru (Opsional)'" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" :required="$method === 'POST'" autocomplete="new-password" placeholder="Minimal 8 karakter" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" :required="$method === 'POST'" autocomplete="new-password" placeholder="Ketik ulang password" />
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: PENGATURAN ASSESSMENT --}}
        <div class="rounded-xl border border-indigo-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-indigo-100 bg-indigo-50/50 px-6 py-4">
                <h3 class="text-base font-semibold text-indigo-900">Pengaturan Assessment Ujian</h3>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <x-input-label for="question_package_id" value="Paket Soal Ujian" />
                    <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" required>
                        <option value="">-- Pilih Paket Soal --</option>
                        @foreach ($packages as $pkg)
                            <option value="{{ $pkg->id }}" data-type="{{ $pkg->type }}" data-has-segments="{{ $pkg->has_segments ? '1' : '0' }}" @selected(old('question_package_id', $user->question_package_id ?? '') == $pkg->id)>{{ $pkg->name }} ({{ \App\Models\QuestionPackage::typeLabel($pkg->type) }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <x-input-label for="assessment_duration_hours" value="Durasi Pengerjaan (Jam)" />
                        <input
                            id="assessment_duration_hours"
                            name="assessment_duration_hours"
                            type="number"
                            min="0.25"
                            max="24"
                            step="0.25"
                            value="{{ old('assessment_duration_hours', round((($user->assessment_duration_minutes ?? null) ?? config('assessment.default_duration_minutes')) / 60, 2)) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-500">Contoh: 1.5 untuk 1 jam 30 menit.</p>
                        <x-input-error :messages="$errors->get('assessment_duration_hours')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="assessment_access_expires_at" value="Akses Ujian Berakhir Pada" />
                        <input
                            id="assessment_access_expires_at"
                            name="assessment_access_expires_at"
                            type="datetime-local"
                            value="{{ old('assessment_access_expires_at', ($user->assessment_access_expires_at ?? null)?->format('Y-m-d\TH:i')) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ada batas waktu login.</p>
                        <x-input-error :messages="$errors->get('assessment_access_expires_at')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="max_attempts" value="Maksimal Percobaan Ujian" />
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
                        <p class="mt-1 text-xs text-gray-500">Kesempatan re-test jika gagal.</p>
                        <x-input-error :messages="$errors->get('max_attempts')" class="mt-2" />
                    </div>
                </div>

                {{-- Segment Config --}}
                <div id="segment-config-section" class="hidden rounded-lg border border-amber-200 bg-amber-50 p-5 mt-4">
                    <div class="flex items-center justify-between border-b border-amber-200 pb-3 mb-3">
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Konfigurasi Segment Soal</p>
                            <p class="text-xs text-amber-700 mt-0.5">Khusus tipe SHE, urutannya otomatis dikunci: PG - Essay - Upload. Anda cukup mengatur durasinya saja.</p>
                        </div>
                        <button type="button" id="add-segment-btn" class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition-colors">+ Tambah Segment</button>
                    </div>
                    <div id="segment-rows" class="space-y-3">
                        @php
                            $oldSegs = old('segment_config', $user->segment_config ?? config('assessment.she_default_segments', [['type' => 'multiple_choice', 'duration' => 30]]));
                            if (! is_array($oldSegs)) { $oldSegs = [['type' => 'multiple_choice', 'duration' => 30]]; }
                        @endphp
                        @foreach ($oldSegs as $idx => $seg)
                            <div class="segment-row flex items-center gap-3">
                                <select name="segment_config[{{ $idx }}][type]" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 flex-1">
                                    <option value="multiple_choice" @selected(($seg['type'] ?? '') === 'multiple_choice')>Pilihan Ganda (Multiple Choice)</option>
                                    <option value="essay" @selected(($seg['type'] ?? '') === 'essay')>Essay Singkat</option>
                                    <option value="upload" @selected(($seg['type'] ?? '') === 'upload')">Upload File Document</option>
                                </select>
                                <div class="flex items-center gap-1">
                                    <input type="number" name="segment_config[{{ $idx }}][duration]" value="{{ $seg['duration'] ?? 30 }}" min="1" max="480" placeholder="Menit" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                                    <span class="text-sm text-gray-500 font-medium">menit</span>
                                </div>
                                <button type="button" class="remove-segment-btn text-rose-400 hover:text-rose-600 p-1 rounded-full hover:bg-rose-50 transition-colors" title="Hapus Segment">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-end">
                        <p class="text-sm font-semibold text-amber-900 bg-amber-100/50 px-3 py-1.5 rounded-md border border-amber-200">Total Waktu Segment: <span id="segment-total" class="text-lg">0</span> menit</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 3: LOKASI & KATEGORI --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Penempatan Lokasi & Kategori</h3>
            </div>
            <div class="p-6 grid gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="site" value="Site (Lokasi)" />
                    <x-text-input id="site" name="site" class="mt-1 block w-full" :value="old('site', $user->site ?? '')" maxlength="100" placeholder="Contoh: Site Kaltim" />
                    <p class="mt-2 text-xs text-gray-500" id="site-helper-text">Kosongkan jika tidak ada site.</p>
                    <x-input-error :messages="$errors->get('site')" class="mt-2" />
                </div>

                <div id="operator-category-field">
                    <x-input-label for="operator_assessment_category_id" value="Kategori Peserta (Khusus Mekanik/Operator)" />
                    <select id="operator_assessment_category_id" name="operator_assessment_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tanpa Kategori / Bukan Operator</option>
                        @foreach (($operatorCategories ?? collect()) as $category)
                            <option value="{{ $category->id }}" @selected(old('operator_assessment_category_id', $user->operator_assessment_category_id ?? '') == $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Digunakan untuk melacak kelompok peserta (Misal: Batch New Hire).</p>
                    <x-input-error :messages="$errors->get('operator_assessment_category_id')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">Batal</a>
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 hover:shadow transition-all">{{ $button }}</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const packageSelect = document.getElementById('question_package_id');
    const operatorCategoryField = document.getElementById('operator-category-field');
    const operatorCategorySelect = document.getElementById('operator_assessment_category_id');
    const segmentSection = document.getElementById('segment-config-section');
    const segmentRows = document.getElementById('segment-rows');
    const addBtn = document.getElementById('add-segment-btn');
    const totalSpan = document.getElementById('segment-total');
    const sheSegmentTypes = ['multiple_choice', 'essay', 'upload'];
    const segmentLabels = {
        multiple_choice: 'Pilihan Ganda (Multiple Choice)',
        essay: 'Essay Singkat',
        upload: 'Upload File Document',
    };
    const sheDefaultDurations = @json(collect(config('assessment.she_default_segments'))->pluck('duration', 'type'));
    let rowIndex = segmentRows ? segmentRows.querySelectorAll('.segment-row').length : 0;

    if (packageSelect) {
        packageSelect.addEventListener('change', toggleSegmentSection);
        toggleSegmentSection();
    }
    updateTotal();

    function toggleSegmentSection() {
        if (!packageSelect) return;
        const selectedOption = packageSelect.querySelector('option:checked');
        const packageType = selectedOption ? selectedOption.dataset.type : '';
        const isShe = packageType === 'she';
        const usesCategory = ['mekanik', 'operator'].includes(packageType);
        const hasSegments = selectedOption && (selectedOption.dataset.hasSegments === '1' || isShe);

        operatorCategoryField?.classList.toggle('opacity-50', !usesCategory);
        if (operatorCategorySelect) {
            operatorCategorySelect.disabled = !usesCategory;
            if (!usesCategory) {
                operatorCategorySelect.value = '';
            }
        }
        if (hasSegments && isShe) {
            enforceSheSegments();
        }

        segmentSection?.classList.toggle('hidden', !hasSegments);
        if (addBtn) {
            addBtn.classList.toggle('hidden', isShe);
            addBtn.disabled = !hasSegments || isShe;
        }

        if (segmentRows) {
            segmentRows.querySelectorAll('select').forEach(function (select) {
                select.disabled = !hasSegments;
                select.classList.toggle('pointer-events-none', isShe);
                select.classList.toggle('bg-amber-100', isShe);
            });
            segmentRows.querySelectorAll('input[type="number"]').forEach(function (input) {
                input.disabled = !hasSegments;
            });
            segmentRows.querySelectorAll('.remove-segment-btn').forEach(function (button) {
                button.disabled = !hasSegments || isShe;
                button.classList.toggle('hidden', isShe);
            });
        }

        updateTotal();
    }

    function updateTotal() {
        if (!segmentRows || !totalSpan) return;
        let total = 0;
        segmentRows.querySelectorAll('input[type="number"]').forEach(function (input) {
            total += parseInt(input.value) || 0;
        });
        totalSpan.textContent = total;
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            appendSegmentRow({
                type: 'multiple_choice',
                duration: 30,
            });
        });
    }

    function enforceSheSegments() {
        if (!segmentRows) return;
        const currentDurations = Array.from(segmentRows.querySelectorAll('.segment-row input[type="number"]'))
            .map(function (input) {
                return parseInt(input.value) || null;
            });

        segmentRows.innerHTML = '';
        rowIndex = 0;
        sheSegmentTypes.forEach(function (type, index) {
            appendSegmentRow({
                type,
                duration: currentDurations[index] || parseInt(sheDefaultDurations[type]) || 30,
            }, true);
        });
    }

    function appendSegmentRow(segment, locked = false) {
        if (!segmentRows) return;
        const row = document.createElement('div');
        row.className = 'segment-row flex items-center gap-3';
        const type = segment.type || 'multiple_choice';
        row.innerHTML = `
            <select name="segment_config[${rowIndex}][type]" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 flex-1 ${locked ? 'pointer-events-none bg-amber-100' : ''}">
                ${sheSegmentTypes.map(function (optionType) {
                    return `<option value="${optionType}" ${optionType === type ? 'selected' : ''}>${segmentLabels[optionType]}</option>`;
                }).join('')}
            </select>
            <div class="flex items-center gap-1">
                <input type="number" name="segment_config[${rowIndex}][duration]" value="${segment.duration || 30}" min="1" max="480" placeholder="Menit" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                <span class="text-sm text-gray-500 font-medium">menit</span>
            </div>
            <button type="button" class="remove-segment-btn text-rose-400 hover:text-rose-600 p-1 rounded-full hover:bg-rose-50 transition-colors ${locked ? 'hidden' : ''}" ${locked ? 'disabled' : ''} title="Hapus Segment">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;
        segmentRows.appendChild(row);
        rowIndex++;
        row.querySelector('input[type="number"]').addEventListener('input', updateTotal);
        row.querySelector('.remove-segment-btn').addEventListener('click', function () {
            row.remove();
            updateTotal();
        });
        updateTotal();
    }

    if (segmentRows) {
        segmentRows.querySelectorAll('.remove-segment-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.segment-row').remove();
                updateTotal();
            });
        });
        segmentRows.querySelectorAll('input[type="number"]').forEach(function (input) {
            input.addEventListener('input', updateTotal);
        });
    }
});
</script>
