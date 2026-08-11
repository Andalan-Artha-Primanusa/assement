@php
    $visibleTypes = Auth::user()->visiblePackageTypes();
    $selectedType = old('type', $package->type ?: ($visibleTypes[0] ?? \App\Models\QuestionPackage::TYPE_OPERATOR));
    $selectedLevel = old('level', $package->level);
    $levelGroups = collect(\App\Models\QuestionPackage::LEVELS_BY_TYPE)
        ->only($visibleTypes)
        ->filter(fn (array $levels): bool => count($levels) > 0);
    $typeMeta = [
        'mekanik' => [
            'label' => 'Mekanik',
            'description' => 'Mechanic, Tyreman, Auto Electrician, Welder',
            'accent' => 'bg-sky-500',
        ],
        'operator' => [
            'label' => 'Operator',
            'description' => 'Paket operator tanpa level khusus',
            'accent' => 'bg-amber-500',
        ],
        'she' => [
            'label' => 'SHE',
            'description' => 'PG, Essay, dan Portfolio bersegment',
            'accent' => 'bg-cyan-500',
        ],
        'hr' => [
            'label' => 'HR',
            'description' => 'Dispatch, Finance, HRGA, Operation, Engineering',
            'accent' => 'bg-rose-500',
        ],
    ];
    $thresholdExamples = [
        ['label' => 'Mekanik M1', 'pertimbangan' => 60, 'lolos' => 65],
        ['label' => 'Mekanik M2', 'pertimbangan' => 55, 'lolos' => 60],
        ['label' => 'Mekanik M3', 'pertimbangan' => 50, 'lolos' => 55],
        ['label' => 'Operator', 'pertimbangan' => 65, 'lolos' => 70],
        ['label' => 'SHE Dept Head', 'pertimbangan' => 65, 'lolos' => 70],
        ['label' => 'SHE Section Head', 'pertimbangan' => 60, 'lolos' => 65],
        ['label' => 'SHE Lead Of', 'pertimbangan' => 55, 'lolos' => 60],
        ['label' => 'HR Admin/Dispatch', 'pertimbangan' => 60, 'lolos' => 70],
    ];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8" data-package-form data-confirm
      data-confirm-title="{{ ($method ?? 'POST') === 'POST' ? 'Tambah paket soal?' : 'Simpan perubahan paket?' }}"
      data-confirm-message="Pastikan tipe, level, threshold, dan status paket sudah benar."
      data-confirm-text="{{ ($method ?? 'POST') === 'POST' ? 'Ya, tambah paket' : 'Ya, simpan perubahan' }}">
    @csrf
    @if (isset($method) && $method !== 'POST')
        @method($method)
    @endif

    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="space-y-5">
            <div>
                <x-input-label for="name" value="Nama Paket" />
                <x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name', $package->name)" placeholder="Contoh: Screening HR Admin HRGA" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Tipe Paket" />
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach ($visibleTypes as $type)
                        @php
                            $meta = $typeMeta[$type] ?? ['label' => ucfirst($type), 'description' => 'Paket assessment', 'accent' => 'bg-gray-500'];
                        @endphp
                        <label class="group relative cursor-pointer rounded-lg border bg-white p-4 transition hover:border-indigo-300 hover:bg-indigo-50/30 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                            <input type="radio" name="type" value="{{ $type }}" class="sr-only" @checked($selectedType === $type)>
                            <span class="flex items-start gap-3">
                                <span class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $meta['accent'] }}"></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-950">{{ $meta['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-gray-500">{{ $meta['description'] }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="level" value="Level / Posisi" />
                    <select id="level" name="level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tanpa level</option>
                        @foreach ($levelGroups as $type => $levels)
                            <optgroup label="{{ \App\Models\QuestionPackage::typeLabel($type) }}">
                                @foreach ($levels as $value => $label)
                                    <option value="{{ $value }}" data-package-type="{{ $type }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">HR memakai level posisi sesuai daftar dari foto.</p>
                    <x-input-error :messages="$errors->get('level')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="min_score_pertimbangan" value="Min. Dipertimbangkan" />
                        <x-text-input id="min_score_pertimbangan" name="min_score_pertimbangan" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_score_pertimbangan', $package->min_score_pertimbangan)" />
                        <x-input-error :messages="$errors->get('min_score_pertimbangan')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="min_score_lolos" value="Min. Lolos" />
                        <x-text-input id="min_score_lolos" name="min_score_lolos" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_score_lolos', $package->min_score_lolos)" />
                        <x-input-error :messages="$errors->get('min_score_lolos')" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>

        <aside class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase text-gray-500">Status Paket</p>
            <div class="mt-4 space-y-3">
                <label class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-3 py-3">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Aktif</span>
                        <span class="block text-xs text-gray-500">Bisa dipakai peserta.</span>
                    </span>
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $package->is_active))>
                </label>
                <label class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-3 py-3">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Sertifikat</span>
                        <span class="block text-xs text-gray-500">Muncul jika lolos.</span>
                    </span>
                    <input type="checkbox" name="is_certificate" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_certificate', $package->is_certificate))>
                </label>
                <label class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-3 py-3">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Segment</span>
                        <span class="block text-xs text-gray-500">Wajib untuk SHE.</span>
                    </span>
                    <input id="has_segments" type="checkbox" name="has_segments" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('has_segments', $package->has_segments))>
                </label>
            </div>
        </aside>
    </section>

    <section>
        <x-input-label for="description" value="Deskripsi" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ringkasan paket, posisi, atau standar kelulusan">{{ old('description', $package->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </section>

    <section class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">Referensi Threshold</p>
        </div>
        <div class="grid gap-px bg-gray-100 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($thresholdExamples as $example)
                <div class="bg-white px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">{{ $example['label'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Pertimbangan {{ $example['pertimbangan'] }} / Lolos {{ $example['lolos'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div id="she-segment-note" class="hidden rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-800">
        Paket SHE otomatis memakai segment PG, Essay, dan Portfolio. Waktu per segment diatur di user yang dibuat.
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
        <a href="{{ route('admin.packages.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-package-form]');
    if (!form) return;

    const typeInputs = Array.from(form.querySelectorAll('input[name="type"]'));
    const levelSelect = form.querySelector('#level');
    const segmentCheckbox = form.querySelector('#has_segments');
    const segmentNote = form.querySelector('#she-segment-note');

    function selectedType() {
        const input = typeInputs.find((item) => item.checked);
        return input ? input.value : '';
    }

    function syncTypeState() {
        const type = selectedType();
        const selectedOption = levelSelect.options[levelSelect.selectedIndex];

        Array.from(levelSelect.options).forEach(function (option) {
            const optionType = option.dataset.packageType;
            if (!optionType) return;
            const visible = optionType === type;
            option.hidden = !visible;
            option.disabled = !visible;
        });

        if (selectedOption && selectedOption.dataset.packageType && selectedOption.dataset.packageType !== type) {
            levelSelect.value = '';
        }

        if (type === 'she') {
            segmentCheckbox.checked = true;
            segmentCheckbox.disabled = true;
            segmentNote.classList.remove('hidden');
        } else {
            segmentCheckbox.disabled = false;
            segmentNote.classList.add('hidden');
        }
    }

    typeInputs.forEach(function (input) {
        input.addEventListener('change', syncTypeState);
    });

    syncTypeState();
});
</script>
