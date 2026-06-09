<form method="POST" action="{{ $action }}" class="space-y-6">
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
                    <option value="{{ $pkg->id }}" @selected(old('question_package_id', $question->question_package_id) == $pkg->id)>{{ $pkg->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
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
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $question->is_active))>
                <span class="text-sm text-gray-700">Aktif</span>
            </label>
        </div>
    </div>

    <div>
        <x-input-label for="text" value="Pertanyaan" />
        <textarea id="text" name="text" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('text', $question->text) }}</textarea>
        <x-input-error :messages="$errors->get('text')" class="mt-2" />
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach (['a', 'b', 'c', 'd'] as $option)
            <div>
                <x-input-label :for="'option_'.$option" :value="'Pilihan '.strtoupper($option)" />
                <textarea id="option_{{ $option }}" name="option_{{ $option }}" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('option_'.$option, $question->{'option_'.$option}) }}</textarea>
                <x-input-error :messages="$errors->get('option_'.$option)" class="mt-2" />
            </div>
        @endforeach
    </div>

    <div>
        <x-input-label for="correct_option" value="Kunci Jawaban" />
        <select id="correct_option" name="correct_option" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['a', 'b', 'c', 'd'] as $option)
                <option value="{{ $option }}" @selected(old('correct_option', $question->correct_option) === $option)>Pilihan {{ strtoupper($option) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('correct_option')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.questions.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>
