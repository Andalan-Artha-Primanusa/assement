<x-app-layout>
    @push('styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            /* Remove box shadow and borders for cleaner print */
            #print-area .shadow-sm {
                box-shadow: none !important;
            }
            #print-area .ring-1 {
                box-shadow: none !important;
            }
            @page {
                size: auto;
                margin: 1.5cm;
            }
        }
    </style>
    @endpush

    <div class="py-12 print:py-0">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 print:px-0 print:max-w-none">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <h1 class="text-2xl font-bold text-gray-900">Detail Penilaian: {{ $interview_assessment->candidate_name }}</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.interview-assessments.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">&larr; Kembali</a>
                    <a href="{{ route('admin.interview-assessments.edit', $interview_assessment) }}" class="rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                    <a href="{{ route('admin.interview-assessments.pdf', $interview_assessment) }}" class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Download PDF</a>
                    <form method="POST" action="{{ route('admin.interview-assessments.destroy', $interview_assessment) }}" data-confirm
                          data-confirm-title="Hapus penilaian interview?"
                          data-confirm-message="Data penilaian {{ $interview_assessment->candidate_name }} akan dihapus permanen."
                          data-confirm-text="Ya, hapus"
                          data-confirm-variant="danger">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                    </form>
                </div>
            </div>

            <div id="print-area" class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-8 print:shadow-none print:ring-0 print:p-0">
                
                <div class="text-center mb-8 border-b pb-6">
                    <h2 class="text-xl font-bold text-gray-900 uppercase">Form Penilaian Interview</h2>
                    <p class="text-sm text-gray-500">Template: {{ $interview_assessment->template->name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-x-12 gap-y-4 mb-8 text-sm">
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Nama Kandidat</span>
                        <span class="text-gray-900">: {{ $interview_assessment->candidate_name }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Jabatan</span>
                        <span class="text-gray-900">: {{ $interview_assessment->job_title ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Jenis Kelamin</span>
                        <span class="text-gray-900">: {{ $interview_assessment->gender == 'L' ? 'Laki-laki' : ($interview_assessment->gender == 'P' ? 'Perempuan' : '-') }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Departemen</span>
                        <span class="text-gray-900">: {{ $interview_assessment->department ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Usia</span>
                        <span class="text-gray-900">: {{ $interview_assessment->age ? $interview_assessment->age . ' Tahun' : '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Lokasi / Site</span>
                        <span class="text-gray-900">: {{ $interview_assessment->location ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Domisili</span>
                        <span class="text-gray-900">: {{ $interview_assessment->domicile ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Tanggal Join</span>
                        <span class="text-gray-900">: {{ $interview_assessment->join_date?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Ekspektasi Gaji</span>
                        <span class="text-gray-900">: {{ $interview_assessment->expected_salary ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-40 font-semibold text-gray-700">Tanggal Interview</span>
                        <span class="text-gray-900">: {{ $interview_assessment->interview_date?->format('d M Y') ?? '-' }}</span>
                    </div>
                </div>

                <div class="mb-8">
                    @foreach($interview_assessment->template->categories as $category)
                        <div class="mb-6">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th colspan="4" class="px-4 py-2 text-left font-bold text-gray-900">{{ $category->name }}</th>
                                    </tr>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 w-12 border-b">No</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 border-b">Aspek Penilaian</th>
                                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700 w-24 border-b">Skor (1-5)</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 border-b">Keterangan / Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($category->aspects as $index => $aspect)
                                        @php
                                            $score = $interview_assessment->scores->where('interview_aspect_id', $aspect->id)->first();
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-700 border-b">{{ $index + 1 }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 border-b">{{ $aspect->name }}</td>
                                            <td class="px-4 py-2 text-center text-sm font-semibold border-b text-indigo-600">{{ $score?->score ?? '-' }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700 border-b">{{ $score?->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-8 mt-8 border-t pt-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 bg-gray-100 p-2 text-center">RINGKASAN HASIL</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <td class="py-2 font-semibold text-gray-700">TOTAL NILAI</td>
                                <td class="py-2 text-right font-bold text-gray-900">{{ $interview_assessment->total_score }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-2 font-semibold text-gray-700">NILAI RATA-RATA</td>
                                <td class="py-2 text-right font-bold text-gray-900">{{ $interview_assessment->average_score }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-2 font-semibold text-gray-700">PERSENTASE (%)</td>
                                <td class="py-2 text-right font-bold text-gray-900">{{ $interview_assessment->percentage }}%</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-gray-700">REKOMENDASI</td>
                                <td class="py-3 text-right">
                                    @php
                                        $recColors = [
                                            'DIREKOMENDASIKAN' => 'bg-emerald-100 text-emerald-800',
                                            'DIPERTIMBANGKAN' => 'bg-amber-100 text-amber-800',
                                            'TIDAK DIREKOMENDASIKAN' => 'bg-red-100 text-red-800',
                                        ];
                                        $color = $recColors[$interview_assessment->recommendation] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="rounded px-2 py-1 text-xs font-bold {{ $color }}">
                                        {{ $interview_assessment->recommendation }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                        <div class="mt-4 text-xs text-gray-500 bg-gray-50 p-3 rounded">
                            <p><strong>RATING SCALE:</strong></p>
                            <p>5 = Sangat Baik | 4 = Baik | 3 = Sedang | 2 = Kurang | 1 = Sangat Kurang</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 bg-gray-100 p-2 text-center">KESIMPULAN & TANDA TANGAN</h3>
                        <div class="mb-4 min-h-[100px] p-3 border rounded text-sm text-gray-700">
                            <strong>Catatan:</strong><br>
                            {{ $interview_assessment->hr_conclusion ?? 'Tidak ada catatan.' }}
                        </div>

                        <div class="flex justify-end mt-8 text-center text-sm">
                            <div class="w-1/2">
                                <p class="mb-4 font-semibold">Penilai</p>
                                @if($interview_assessment->signature_path)
                                    <img src="{{ route('files.show', $interview_assessment->signature_path) }}" alt="Tanda tangan penilai" class="mx-auto mb-4 h-20 max-w-[240px] object-contain">
                                @else
                                    <p class="mb-16"></p>
                                @endif
                                <p class="underline font-bold">{{ $interview_assessment->hr_interviewer_name ?? '(...................................)' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end">
                    <button onclick="window.print()" class="rounded-md bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 print:hidden shadow-sm">
                        Cetak / Print PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
