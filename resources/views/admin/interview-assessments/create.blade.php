<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Tambah Penilaian Interview</h1>
                <a href="{{ route('admin.interview-assessments.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    &larr; Kembali ke daftar
                </a>
            </div>

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                @if(!request('template_id'))
                    <form action="{{ route('admin.interview-assessments.create') }}" method="GET" class="max-w-md">
                        <div class="mb-4">
                            <label for="template_id" class="block text-sm font-medium text-gray-700">Pilih Template Form</label>
                            <select id="template_id" name="template_id" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Template --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Lanjutkan</button>
                    </form>
                @else
                    @php
                        $selectedTemplate = $templates->where('id', request('template_id'))->first();
                    @endphp
                    <form action="{{ route('admin.interview-assessments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="interview_template_id" value="{{ $selectedTemplate->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-b pb-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Kandidat</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama Kandidat</label>
                                        <input type="text" name="candidate_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jabatan yang Dilamar</label>
                                        <input type="text" name="job_title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Departemen</label>
                                        <input type="text" name="department" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                            <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="L">Laki-laki</option>
                                                <option value="P">Perempuan</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Usia</label>
                                            <input type="number" name="age" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Lokasi / Site</label>
                                        <input type="text" name="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Domisili</label>
                                        <input type="text" name="domicile" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Ekspektasi Gaji</label>
                                        <input type="text" name="expected_salary" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Tanggal Interview</label>
                                            <input type="date" name="interview_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Tanggal Join (Jika lolos)</label>
                                            <input type="date" name="join_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Penilaian Aspek (Skala 1-5)</h3>
                            
                            @foreach($selectedTemplate->categories as $category)
                                <div class="mb-6">
                                    <h4 class="font-bold text-gray-800 bg-gray-100 p-2 rounded">{{ $category->name }}</h4>
                                    <table class="min-w-full divide-y divide-gray-200 mt-2">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 w-12">No</th>
                                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Aspek Penilaian</th>
                                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-900 w-24">Skor</th>
                                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Keterangan / Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($category->aspects as $index => $aspect)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $aspect->name }}</td>
                                                    <td class="px-4 py-3">
                                                        <select name="scores[{{ $aspect->id }}][score]" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                            <option value="">-</option>
                                                            <option value="1">1</option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                            <option value="4">4</option>
                                                            <option value="5">5</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="text" name="scores[{{ $aspect->id }}][notes]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Catatan singkat...">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mb-8 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Kesimpulan & Catatan</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kesimpulan / Catatan Akhir</label>
                                    <textarea name="hr_conclusion" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Tuliskan kesimpulan dari proses interview..."></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama Penilai 1 (HR)</label>
                                        <input type="text" name="hr_interviewer_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama Penilai 2 (User)</label>
                                        <input type="text" name="user_interviewer_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.interview-assessments.index') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Batal</a>
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan Penilaian</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
