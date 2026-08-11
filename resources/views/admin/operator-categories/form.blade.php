<form method="POST" action="{{ $action }}" class="space-y-6" data-confirm
      data-confirm-title="{{ ($method ?? 'POST') === 'POST' ? 'Tambah kategori Operator?' : 'Simpan kategori Operator?' }}"
      data-confirm-message="Kategori ini akan dipakai untuk memisahkan paket dan soal Operator."
      data-confirm-text="{{ ($method ?? 'POST') === 'POST' ? 'Ya, tambah kategori' : 'Ya, simpan kategori' }}">
    @csrf
    @if (isset($method) && $method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Nama Kategori" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $category->name)" placeholder="Contoh: New Hire" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Catatan singkat untuk kategori ini">{{ old('description', $category->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $category->is_active))>
        <span class="text-sm text-gray-700">Aktif</span>
    </label>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.operator-categories.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ $button }}</button>
    </div>
</form>
