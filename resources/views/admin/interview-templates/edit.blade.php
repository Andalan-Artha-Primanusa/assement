<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Edit Template Interview</h2>
                <p class="mt-1 text-sm text-gray-500">Ubah detail template interview beserta kategori dan aspek penilaiannya.</p>
            </div>
            <a href="{{ route('admin.interview-templates.index') }}" class="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6" x-data="templateForm({{ Js::from($interview_template->categories) }})">
                <form method="POST" action="{{ route('admin.interview-templates.update', $interview_template) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Nama Template" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $interview_template->name)" placeholder="Contoh: Form Interview Mekanik" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="type" value="Tipe / Kategori" />
                            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="mekanik" @selected($interview_template->type === 'mekanik')>Mekanik</option>
                                <option value="operator" @selected($interview_template->type === 'operator')>Operator</option>
                                <option value="hr" @selected($interview_template->type === 'hr')>HR</option>
                                <option value="she" @selected($interview_template->type === 'she')>SHE</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="min_recommended_percentage" value="Min. Persentase Rekomendasi (%)" />
                            <x-text-input type="number" id="min_recommended_percentage" name="min_recommended_percentage" class="mt-1 block w-full" :value="old('min_recommended_percentage', $interview_template->min_recommended_percentage)" min="0" max="100" required />
                            <x-input-error :messages="$errors->get('min_recommended_percentage')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="min_considered_percentage" value="Min. Persentase Dipertimbangkan (%)" />
                            <x-text-input type="number" id="min_considered_percentage" name="min_considered_percentage" class="mt-1 block w-full" :value="old('min_considered_percentage', $interview_template->min_considered_percentage)" min="0" max="100" required />
                            <x-input-error :messages="$errors->get('min_considered_percentage')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_active', $interview_template->is_active))>
                        <x-input-label for="is_active" value="Aktif" class="inline" />
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Kategori & Aspek Penilaian</h3>
                            <button type="button" @click="addCategory()" class="inline-flex items-center gap-1.5 rounded bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Kategori
                            </button>
                        </div>

                        <div class="space-y-6">
                            <template x-for="(category, cIndex) in categories" :key="cIndex">
                                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50/50 space-y-4">
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" :name="`categories[${cIndex}][id]`" x-model="category.id">
                                        <div class="flex-1">
                                            <input type="text" :name="`categories[${cIndex}][name]`" x-model="category.name" placeholder="Nama Kategori (Contoh: Pengetahuan Teknis)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-semibold" required>
                                        </div>
                                        <button type="button" @click="removeCategory(cIndex)" class="text-rose-600 hover:text-rose-800 text-xs font-medium px-2.5 py-1.5 rounded hover:bg-rose-50">
                                            Hapus Kategori
                                        </button>
                                    </div>

                                    <div class="pl-6 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium text-gray-500">Aspek Penilaian</span>
                                            <button type="button" @click="addAspect(cIndex)" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                                                + Tambah Aspek
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(aspect, aIndex) in category.aspects" :key="aIndex">
                                                <div class="flex items-center gap-2">
                                                    <input type="hidden" :name="`categories[${cIndex}][aspects][${aIndex}][id]`" x-model="aspect.id">
                                                    <span class="text-xs text-gray-400 font-medium w-6 text-right" x-text="aIndex + 1"></span>
                                                    <div class="flex-1">
                                                        <input type="text" :name="`categories[${cIndex}][aspects][${aIndex}][name]`" x-model="aspect.name" placeholder="Nama Aspek Penilaian (Contoh: Trouble Shooting)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                                    </div>
                                                    <button type="button" @click="removeAspect(cIndex, aIndex)" class="text-gray-400 hover:text-rose-600 p-1">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                        <a href="{{ route('admin.interview-templates.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function templateForm(initialCategories) {
            return {
                categories: initialCategories && initialCategories.length > 0 ? initialCategories : [
                    {
                        name: '',
                        aspects: [
                            { name: '' }
                        ]
                    }
                ],
                addCategory() {
                    this.categories.push({
                        name: '',
                        aspects: [
                            { name: '' }
                        ]
                    });
                },
                removeCategory(cIndex) {
                    if (this.categories.length > 1) {
                        this.categories.splice(cIndex, 1);
                    } else {
                        alert('Minimal harus memiliki 1 kategori.');
                    }
                },
                addAspect(cIndex) {
                    this.categories[cIndex].aspects.push({ name: '' });
                },
                removeAspect(cIndex, aIndex) {
                    if (this.categories[cIndex].aspects.length > 1) {
                        this.categories[cIndex].aspects.splice(aIndex, 1);
                    } else {
                        alert('Minimal harus memiliki 1 aspek penilaian.');
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
