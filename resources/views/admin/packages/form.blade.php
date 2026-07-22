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

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="type" value="Tipe Paket" />
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="mekanik" @selected(old('type', $package->type) == 'mekanik')>Mekanik</option>
                <option value="operator" @selected(old('type', $package->type) == 'operator')>Operator</option>
                <option value="she" @selected(old('type', $package->type) == 'she')>SHE</option>
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="level" value="Level" />
            <select id="level" name="level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" @selected(old('level', $package->level) == '')>-</option>
                <optgroup label="Mekanik">
                    <option value="M1" @selected(old('level', $package->level) == 'M1')>M1 - Dasar</option>
                    <option value="M2" @selected(old('level', $package->level) == 'M2')>M2 - Menengah</option>
                    <option value="M3" @selected(old('level', $package->level) == 'M3')>M3 - Lanjutan</option>
                </optgroup>
                <optgroup label="SHE">
                    <option value="Basic" @selected(old('level', $package->level) == 'Basic')>Basic</option>
                    <option value="Advanced" @selected(old('level', $package->level) == 'Advanced')>Advanced</option>
                </optgroup>
            </select>
            <p class="mt-1 text-xs text-gray-500">Pilih level sesuai tipe paket.</p>
            <x-input-error :messages="$errors->get('level')" class="mt-2" />
        </div>
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
            <p class="mt-1 text-xs text-gray-500">>= ambang ini = Dipertimbangkan</p>
            <x-input-error :messages="$errors->get('min_score_pertimbangan')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="min_score_lolos" value="Min. Nilai Lolos" />
            <x-text-input id="min_score_lolos" name="min_score_lolos" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('min_score_lolos', $package->min_score_lolos)" />
            <p class="mt-1 text-xs text-gray-500">>= ambang ini = Lolos</p>
            <x-input-error :messages="$errors->get('min_score_lolos')" class="mt-2" />
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $package->is_active))>
                <span class="text-sm text-gray-700">Aktif</span>
            </label>
        </div>
    </div>

    <div class="rounded-md bg-blue-50 border border-blue-200 p-4">
        <p class="text-sm text-blue-800 font-medium">Contoh Threshold:</p>
        <ul class="mt-1 text-xs text-blue-700 space-y-1">
            <li><strong>Mekanik M1:</strong> Pertimbangkan >= 60, Lolos >= 65</li>
            <li><strong>Mekanik M2:</strong> Pertimbangkan >= 55, Lolos >= 60</li>
            <li><strong>Mekanik M3:</strong> Pertimbangkan >= 50, Lolos >= 55</li>
            <li><strong>Operator:</strong> Pertimbangkan >= 65, Lolos >= 70</li>
            <li><strong>SHE Basic:</strong> Pertimbangkan >= 60, Lolos >= 65</li>
            <li><strong>SHE Advanced:</strong> Pertimbangkan >= 65, Lolos >= 70</li>
        </ul>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.packages.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>
