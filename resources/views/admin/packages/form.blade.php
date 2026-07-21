<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if (isset($method) && $method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Nama Paket" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $package->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi (opsional)" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $package->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="min_score_pertimbangan" value="Min. Nilai Dipertimbangkan" />
            <x-text-input id="min_score_pertimbangan" name="min_score_pertimbangan" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_score_pertimbangan', $package->min_score_pertimbangan)" />
            <p class="mt-1 text-xs text-gray-500">Nilai >= ambang ini = Dipertimbangkan</p>
            <x-input-error :messages="$errors->get('min_score_pertimbangan')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="min_score_lolos" value="Min. Nilai Lolos" />
            <x-text-input id="min_score_lolos" name="min_score_lolos" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_score_lolos', $package->min_score_lolos)" />
            <p class="mt-1 text-xs text-gray-500">Nilai >= ambang ini = Lolos</p>
            <x-input-error :messages="$errors->get('min_score_lolos')" class="mt-2" />
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $package->is_active))>
                <span class="text-sm text-gray-700">Aktif</span>
            </label>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.packages.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>
