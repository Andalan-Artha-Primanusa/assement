<x-app-layout>
    @php
        $defaultImportCategory = \App\Models\QuestionPackage::typeLabel(Auth::user()->visiblePackageTypes()[0] ?? \App\Models\QuestionPackage::TYPE_MEKANIK);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Soal dari Excel') }}</h2>
            <a href="{{ $selectedPackageId ? route('admin.packages.questions', $selectedPackageId) : route('admin.questions.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Upload File Excel</h3>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.questions.import.template') }}" class="inline-flex items-center gap-2 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Template Umum/SHE
                        </a>
                        <a href="{{ route('admin.questions.import.template', ['type' => 'operator']) }}" class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Template Point
                        </a>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Format kolom: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono">type, text, option_a, option_b, option_c, option_d, correct_option, category, difficulty, points</code>
                </p>
                <div class="mt-2 rounded-md bg-blue-50 p-3 text-xs text-blue-800">
                    <p class="font-semibold">Tipe soal:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        <li><strong>multiple_choice</strong> - wajib isi option_a s/d option_d + correct_option (a/b/c/d)</li>
                        <li><strong>true_false</strong> - Benar/Salah, correct_option isi <strong>a</strong> untuk Benar atau <strong>b</strong> untuk Salah</li>
                        <li><strong>essay</strong> - khusus paket SHE, kolom pilihan & correct_option dikosongkan</li>
                        <li><strong>upload</strong> - khusus paket SHE, peserta upload file</li>
                    </ul>
                    <p class="mt-1">Mekanik, Operator, dan HR memakai multiple_choice atau true_false. Kolom <strong>points</strong> dipakai khusus Operator dan HR sebagai nilai/bobot per soal.</p>
                </div>

                <form method="POST" action="{{ route('admin.questions.import') }}" enctype="multipart/form-data" class="mt-6 space-y-5" data-confirm
                      data-confirm-title="Import soal dari file?"
                      data-confirm-message="Pastikan format file sudah sesuai template agar soal masuk ke paket yang benar."
                      data-confirm-text="Ya, import soal">
                    @csrf

                    <div>
                        <x-input-label for="file" value="File Excel (.xlsx)" />
                        <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="question_package_id" value="Paket Soal (opsional)" />
                            <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tanpa paket</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" data-package-type="{{ $package->type }}" @selected(($selectedPackageId ?? null) == $package->id)>{{ $package->name }} ({{ \App\Models\QuestionPackage::typeLabel($package->type) }}{{ $package->level ? ' - '.$package->level : '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="category" value="Kategori Default" />
                            <input id="category" type="text" name="category" value="{{ $defaultImportCategory }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <x-input-label for="difficulty" value="Kesulitan Default" />
                            <input id="difficulty" type="text" name="difficulty" value="basic" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Import Soal</x-primary-button>
                        <a href="{{ $selectedPackageId ? route('admin.packages.questions', $selectedPackageId) : route('admin.questions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                    </div>
                </form>
            </div>

            <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                <h4 class="font-semibold text-gray-900">Contoh Format Excel</h4>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs font-mono">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">type</th>
                                <th class="px-3 py-2 text-left">text</th>
                                <th class="px-3 py-2 text-left">option_a</th>
                                <th class="px-3 py-2 text-left">option_b</th>
                                <th class="px-3 py-2 text-left">option_c</th>
                                <th class="px-3 py-2 text-left">option_d</th>
                                <th class="px-3 py-2 text-left">correct</th>
                                <th class="px-3 py-2 text-left">category</th>
                                <th class="px-3 py-2 text-left">difficulty</th>
                                <th class="px-3 py-2 text-left">points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-3 py-2 text-indigo-600">multiple_choice</td>
                                <td class="px-3 py-2">Apa fungsi oli mesin?</td>
                                <td class="px-3 py-2">Melumasi</td>
                                <td class="px-3 py-2">Mendinginkan</td>
                                <td class="px-3 py-2">Membersihkan</td>
                                <td class="px-3 py-2">Semua benar</td>
                                <td class="px-3 py-2 text-emerald-600">d</td>
                                <td class="px-3 py-2">Engine</td>
                                <td class="px-3 py-2">basic</td>
                                <td class="px-3 py-2 text-gray-400">1</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-indigo-600">multiple_choice</td>
                                <td class="px-3 py-2">Data personal karyawan wajib dijaga karena termasuk?</td>
                                <td class="px-3 py-2">Informasi publik</td>
                                <td class="px-3 py-2">Data rahasia perusahaan</td>
                                <td class="px-3 py-2">Materi promosi</td>
                                <td class="px-3 py-2">Dokumen operasional umum</td>
                                <td class="px-3 py-2 text-emerald-600">b</td>
                                <td class="px-3 py-2">Kerahasiaan Data</td>
                                <td class="px-3 py-2">intermediate</td>
                                <td class="px-3 py-2 text-rose-600">5</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-amber-600">essay</td>
                                <td class="px-3 py-2">Jelaskan proses perawatan harian pada heavy equipment!</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2">Maintenance</td>
                                <td class="px-3 py-2">intermediate</td>
                                <td class="px-3 py-2 text-gray-400">1</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-rose-600">upload</td>
                                <td class="px-3 py-2">Upload foto hasil inspeksi undercarriage unit!</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2 text-gray-400">-</td>
                                <td class="px-3 py-2">Inspection</td>
                                <td class="px-3 py-2">advanced</td>
                                <td class="px-3 py-2 text-gray-400">1</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Baris pertama (header) otomatis di-skip. Untuk Operator dan HR, isi points dengan nilai/bobot soal; contoh essay/upload hanya dipakai saat paket yang dipilih adalah SHE.</p>
            </div>
        </div>
    </div>
</x-app-layout>
