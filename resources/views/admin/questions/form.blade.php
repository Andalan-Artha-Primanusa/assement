<form method="POST" action="{{ $action }}" class="space-y-6" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-4">
        <div>
            <x-input-label for="question_package_id" value="Paket Soal" />
            <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tanpa Paket</option>
                @foreach ($packages as $pkg)
                    <option value="{{ $pkg->id }}" @selected(old('question_package_id', $question->question_package_id) == $pkg->id)>{{ $pkg->name }} ({{ \App\Models\QuestionPackage::typeLabel($pkg->type) }}{{ $pkg->level ? ' - '.$pkg->level : '' }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="type" value="Tipe Soal" />
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['multiple_choice' => 'Multiple Choice', 'essay' => 'Essay', 'upload' => 'Upload File'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $question->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
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
                <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="h-24 rounded-md border border-gray-200 object-contain">
                <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-red-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                    Hapus gambar
                </label>
            </div>
        @endif
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <div id="mc-fields" class="{{ $question->type !== 'multiple_choice' ? 'hidden' : '' }}">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach (['a', 'b', 'c', 'd'] as $option)
                <div>
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
                    <option value="{{ $option }}" @selected(old('correct_option', $question->correct_option) === $option)>Pilihan {{ strtoupper($option) }}</option>
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
    document.getElementById('type').addEventListener('change', function() {
        const mcFields = document.getElementById('mc-fields');
        const essayInfo = document.getElementById('essay-info');
        const uploadInfo = document.getElementById('upload-info');

        mcFields.classList.add('hidden');
        essayInfo.classList.add('hidden');
        uploadInfo.classList.add('hidden');

        if (this.value === 'multiple_choice') {
            mcFields.classList.remove('hidden');
        } else if (this.value === 'essay') {
            essayInfo.classList.remove('hidden');
        } else if (this.value === 'upload') {
            uploadInfo.classList.remove('hidden');
        }
    });
</script>
