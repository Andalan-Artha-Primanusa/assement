<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Edit Penilaian Interview</h1>
                <a href="{{ route('admin.interview-assessments.show', $interview_assessment) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    &larr; Kembali ke detail
                </a>
            </div>

            @php
                $selectedTemplate = $interview_assessment->template;
                $scoreMap = $interview_assessment->scores->keyBy('interview_aspect_id');
            @endphp

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <form action="{{ route('admin.interview-assessments.update', $interview_assessment) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="interview_template_id" value="{{ $selectedTemplate->id }}">

                    <div class="mb-5 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        Template: <span class="font-semibold">{{ $selectedTemplate->name }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-b pb-8">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Kandidat</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Kandidat</label>
                                    <input type="text" name="candidate_name" value="{{ old('candidate_name', $interview_assessment->candidate_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jabatan yang Dilamar</label>
                                    <input type="text" name="job_title" value="{{ old('job_title', $interview_assessment->job_title) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Departemen</label>
                                    <input type="text" name="department" value="{{ old('department', $interview_assessment->department) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                        <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="L" @selected(old('gender', $interview_assessment->gender) === 'L')>Laki-laki</option>
                                            <option value="P" @selected(old('gender', $interview_assessment->gender) === 'P')>Perempuan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Usia</label>
                                        <input type="number" name="age" value="{{ old('age', $interview_assessment->age) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Lokasi / Site</label>
                                    <input type="text" name="location" value="{{ old('location', $interview_assessment->location) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Domisili</label>
                                    <input type="text" name="domicile" value="{{ old('domicile', $interview_assessment->domicile) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Ekspektasi Gaji</label>
                                    <input type="text" name="expected_salary" value="{{ old('expected_salary', $interview_assessment->expected_salary) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Interview</label>
                                        <input type="date" name="interview_date" value="{{ old('interview_date', $interview_assessment->interview_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Join (Jika lolos)</label>
                                        <input type="date" name="join_date" value="{{ old('join_date', $interview_assessment->join_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                                            @php($score = $scoreMap->get($aspect->id))
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $aspect->name }}</td>
                                                <td class="px-4 py-3">
                                                    <select name="scores[{{ $aspect->id }}][score]" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">-</option>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}" @selected((string) old('scores.'.$aspect->id.'.score', $score?->score) === (string) $i)>{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="text" name="scores[{{ $aspect->id }}][notes]" value="{{ old('scores.'.$aspect->id.'.notes', $score?->notes) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Catatan singkat...">
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
                                <textarea name="hr_conclusion" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('hr_conclusion', $interview_assessment->hr_conclusion) }}</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nama Penilai</label>
                                    <input type="text" name="hr_interviewer_name" value="{{ old('hr_interviewer_name', $interview_assessment->hr_interviewer_name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Upload Tanda Tangan</label>
                                    <input type="file" name="signature" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="mt-1 text-xs text-gray-500">Upload gambar baru jika ingin mengganti tanda tangan. Maksimal 2MB.</p>
                                    @if($interview_assessment->signature_path)
                                        <div class="mt-3 rounded-md border border-gray-200 bg-gray-50 p-3">
                                            <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Tanda tangan saat ini</p>
                                            <img src="{{ route('files.show', $interview_assessment->signature_path) }}" alt="Tanda tangan" class="h-16 max-w-[220px] object-contain">
                                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="remove_signature" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                Hapus tanda tangan
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.interview-assessments.show', $interview_assessment) }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
