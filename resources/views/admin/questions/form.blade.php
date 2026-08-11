<form method="POST" action="{{ $action }}" class="space-y-6" enctype="multipart/form-data" data-confirm
      data-confirm-title="{{ $method === 'POST' ? 'Tambah soal?' : 'Simpan perubahan soal?' }}"
      data-confirm-message="Pastikan pertanyaan, pilihan jawaban, kunci, dan paket soal sudah sesuai."
      data-confirm-text="{{ $method === 'POST' ? 'Ya, tambah soal' : 'Ya, simpan soal' }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <x-input-label for="question_package_id" value="Paket Soal" />
            <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tanpa Paket</option>
                @foreach ($packages as $pkg)
                    <option value="{{ $pkg->id }}" data-package-type="{{ $pkg->type }}" @selected(old('question_package_id', $question->question_package_id) == $pkg->id)>{{ $pkg->name }} ({{ \App\Models\QuestionPackage::typeLabel($pkg->type) }}{{ $pkg->level ? ' - '.$pkg->level : '' }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="type" value="Tipe Soal" />
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['multiple_choice' => 'Multiple Choice', 'true_false' => 'Benar / Salah', 'essay' => 'Essay', 'upload' => 'Upload File'] as $value => $label)
                    <option value="{{ $value }}" data-manual-review="{{ in_array($value, ['essay', 'upload'], true) ? 'true' : 'false' }}" @selected(old('type', $question->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="manual-type-note" class="mt-1 text-xs text-gray-500">Essay dan Upload File hanya tersedia untuk paket SHE.</p>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="category" value="Kategori" />
            <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category', $question->category)" required />
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="difficulty" value="Level" />
            <select id="difficulty" name="difficulty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['basic' => 'Basic', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('difficulty', $question->difficulty) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('difficulty')" class="mt-2" />
        </div>
        <div id="points-field" class="hidden">
            <x-input-label for="points" value="Nilai Soal" />
            <input id="points" type="number" name="points" value="{{ old('points', $question->points ?? 1) }}" min="0.01" max="1000" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1 text-xs text-gray-500">Dipakai sebagai bobot nilai khusus paket Operator dan HR.</p>
            <x-input-error :messages="$errors->get('points')" class="mt-2" />
        </div>
    </div>

    <div>
        <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $question->is_active))>
            <span class="text-sm text-gray-700">Aktif</span>
        </label>
    </div>

    <div>
        <x-input-label for="text" value="Pertanyaan" />
        <textarea id="text" name="text" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('text', $question->text) }}</textarea>
        <x-input-error :messages="$errors->get('text')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="image" value="Gambar Soal (opsional)" />
        <div class="mt-1 flex items-center gap-4">
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
        <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF, WebP (max 2MB). Gambar akan ditampilkan di bawah pertanyaan saat assessment.</p>
        @if ($question->image)
            <div class="mt-2">
                <p class="text-xs text-gray-500 mb-1">Gambar saat ini:</p>
                <img src="{{ $question->imageUrl() }}" alt="Gambar soal" class="h-24 rounded-md border border-gray-200 object-contain">
                <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-red-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                    Hapus gambar
                </label>
            </div>
        @endif
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <div id="mc-fields" class="{{ ! in_array($question->type, ['multiple_choice', 'true_false'], true) ? 'hidden' : '' }}">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach (['a', 'b', 'c', 'd'] as $option)
                <div data-option-field="{{ $option }}">
                    <x-input-label :for="'option_'.$option" :value="'Pilihan '.strtoupper($option)" />
                    <textarea id="option_{{ $option }}" name="option_{{ $option }}" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('option_'.$option, $question->{'option_'.$option}) }}</textarea>
                    <x-input-error :messages="$errors->get('option_'.$option)" class="mt-2" />
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <x-input-label for="correct_option" value="Kunci Jawaban" />
            <select id="correct_option" name="correct_option" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['a', 'b', 'c', 'd'] as $option)
                    <option value="{{ $option }}" data-correct-option="{{ $option }}" @selected(old('correct_option', $question->correct_option) === $option)>Pilihan {{ strtoupper($option) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('correct_option')" class="mt-2" />
        </div>
    </div>

    <div id="essay-info" class="{{ $question->type !== 'essay' ? 'hidden' : '' }}">
        <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
            Soal Essay akan dinilai secara manual oleh admin setelah peserta mengirim jawaban.
        </div>
    </div>

    <div id="upload-info" class="{{ $question->type !== 'upload' ? 'hidden' : '' }}">
        <div class="rounded-md bg-purple-50 border border-purple-200 px-4 py-3 text-sm text-purple-800">
            Soal Upload memungkinkan peserta mengunggah file (PDF, gambar, dokumen). File akan dinilai oleh admin.
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        @if ($question->question_package_id)
            <a href="{{ route('admin.packages.questions', $question->question_package_id) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        @else
            <a href="{{ route('admin.questions.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        @endif
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>

<script>
    (() => {
        const packageSelect = document.getElementById('question_package_id');
        const typeSelect = document.getElementById('type');
        const mcFields = document.getElementById('mc-fields');
        const essayInfo = document.getElementById('essay-info');
        const uploadInfo = document.getElementById('upload-info');
        const manualTypeNote = document.getElementById('manual-type-note');
        const pointsField = document.getElementById('points-field');
        const pointsInput = document.getElementById('points');
        const optionA = document.getElementById('option_a');
        const optionB = document.getElementById('option_b');
        const optionFields = document.querySelectorAll('[data-option-field]');
        const correctOptions = document.querySelectorAll('[data-correct-option]');

        function selectedPackageType() {
            const selectedOption = packageSelect?.options[packageSelect.selectedIndex];

            return selectedOption?.dataset.packageType || '';
        }

        function syncVisibleFields() {
            mcFields.classList.add('hidden');
            essayInfo.classList.add('hidden');
            uploadInfo.classList.add('hidden');

            if (['multiple_choice', 'true_false'].includes(typeSelect.value)) {
                mcFields.classList.remove('hidden');
            } else if (typeSelect.value === 'essay') {
                essayInfo.classList.remove('hidden');
            } else if (typeSelect.value === 'upload') {
                uploadInfo.classList.remove('hidden');
            }
        }

        function syncAllowedTypes() {
            const packageType = selectedPackageType();
            const isShePackage = packageType === 'she';
            const usesQuestionPoints = ['operator', 'hr'].includes(packageType);

            typeSelect.querySelectorAll('option[data-manual-review="true"]').forEach((option) => {
                option.disabled = ! isShePackage;
                option.hidden = ! isShePackage;
            });

            const trueFalseOption = typeSelect.querySelector('option[value="true_false"]');
            trueFalseOption.disabled = isShePackage;
            trueFalseOption.hidden = isShePackage;

            if (! isShePackage && ['essay', 'upload'].includes(typeSelect.value)) {
                typeSelect.value = 'multiple_choice';
            }

            if (isShePackage && typeSelect.value === 'true_false') {
                typeSelect.value = 'multiple_choice';
            }

            manualTypeNote.classList.toggle('text-amber-600', ! isShePackage);
            manualTypeNote.classList.toggle('text-gray-500', isShePackage);
            pointsField.classList.toggle('hidden', ! usesQuestionPoints);

            if (! usesQuestionPoints) {
                pointsInput.value = '1';
            }

            syncVisibleFields();
            syncTrueFalseFields();
        }

        function syncTrueFalseFields() {
            const isTrueFalse = typeSelect.value === 'true_false';

            optionFields.forEach((field) => {
                const option = field.dataset.optionField;
                field.classList.toggle('hidden', isTrueFalse && ! ['a', 'b'].includes(option));
            });

            correctOptions.forEach((option) => {
                const value = option.dataset.correctOption;
                option.hidden = isTrueFalse && ! ['a', 'b'].includes(value);
                option.disabled = isTrueFalse && ! ['a', 'b'].includes(value);
                option.textContent = isTrueFalse
                    ? (value === 'a' ? 'Benar' : (value === 'b' ? 'Salah' : option.textContent))
                    : `Pilihan ${value.toUpperCase()}`;
            });

            if (isTrueFalse) {
                optionA.value = 'Benar';
                optionB.value = 'Salah';

                if (! ['a', 'b'].includes(document.getElementById('correct_option').value)) {
                    document.getElementById('correct_option').value = 'a';
                }
            }
        }

        typeSelect.addEventListener('change', () => {
            syncVisibleFields();
            syncTrueFalseFields();
        });
        packageSelect.addEventListener('change', syncAllowedTypes);
        syncAllowedTypes();
    })();
</script>
