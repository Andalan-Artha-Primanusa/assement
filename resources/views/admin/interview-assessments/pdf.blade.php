<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; line-height: 1.35; }
        .toolbar { margin: 14px auto; max-width: 820px; display: flex; justify-content: space-between; align-items: center; font-family: Arial, sans-serif; }
        .toolbar a, .toolbar button { border: 1px solid #d1d5db; background: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #374151; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #111827; color: #fff; border-color: #111827; }
        .sheet { max-width: 820px; margin: 0 auto 24px; background: #fff; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .subtitle { color: #4b5563; margin-top: 3px; }
        .section-title { background: #111827; color: #fff; font-weight: bold; padding: 6px 8px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; }
        th { background: #f3f4f6; font-weight: bold; }
        .info td { border: 0; padding: 3px 4px; }
        .label { color: #4b5563; font-weight: bold; width: 23%; }
        .value { color: #111827; width: 27%; }
        .score { text-align: center; font-weight: bold; width: 45px; }
        .summary td { padding: 7px 8px; }
        .recommendation { font-weight: bold; text-align: center; padding: 8px; border: 1px solid #111827; }
        .notes { min-height: 54px; border: 1px solid #d1d5db; padding: 8px; }
        .signature { margin-top: 24px; width: 320px; float: right; text-align: center; }
        .signature-space { height: 95px; }
        .signature-image { height: 110px; max-width: 300px; object-fit: contain; margin: 6px auto 2px; display: block; }
        .small { font-size: 9px; color: #6b7280; }
        @media print {
            .toolbar { display: none; }
            .sheet { max-width: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('admin.interview-assessments.show', $interview_assessment) }}">&larr; Kembali</a>
        <button onclick="window.print()">Cetak / Save as PDF</button>
    </div>

    <div class="sheet">
    <div class="header">
        <p class="title">Form Penilaian Interview</p>
        <div class="subtitle">
            Template: {{ $interview_assessment->template->name }} |
            Tanggal: {{ $interview_assessment->interview_date?->format('d M Y') ?? '-' }}
        </div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Nama Kandidat</td>
            <td class="value">: {{ $interview_assessment->candidate_name }}</td>
            <td class="label">Jabatan</td>
            <td class="value">: {{ $interview_assessment->job_title ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Departemen</td>
            <td class="value">: {{ $interview_assessment->department ?? '-' }}</td>
            <td class="label">Lokasi / Site</td>
            <td class="value">: {{ $interview_assessment->location ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="value">: {{ $interview_assessment->gender === 'L' ? 'Laki-laki' : ($interview_assessment->gender === 'P' ? 'Perempuan' : '-') }}</td>
            <td class="label">Usia</td>
            <td class="value">: {{ $interview_assessment->age ? $interview_assessment->age.' Tahun' : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Domisili</td>
            <td class="value">: {{ $interview_assessment->domicile ?? '-' }}</td>
            <td class="label">Tanggal Join</td>
            <td class="value">: {{ $interview_assessment->join_date?->format('d M Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Ekspektasi Gaji</td>
            <td class="value">: {{ $interview_assessment->expected_salary ?? '-' }}</td>
            <td class="label">Penilai</td>
            <td class="value">: {{ $interview_assessment->hr_interviewer_name ?? '-' }}</td>
        </tr>
    </table>

    @foreach($interview_assessment->template->categories as $category)
        <div class="section-title">{{ $category->name }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 28px;">No</th>
                    <th>Aspek Penilaian</th>
                    <th style="width: 48px;">Skor</th>
                    <th style="width: 185px;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($category->aspects as $index => $aspect)
                    @php($score = $interview_assessment->scores->where('interview_aspect_id', $aspect->id)->first())
                    <tr>
                        <td class="score">{{ $index + 1 }}</td>
                        <td>{{ $aspect->name }}</td>
                        <td class="score">{{ $score?->score ?? '-' }}</td>
                        <td>{{ $score?->notes ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="section-title">Ringkasan Hasil</div>
    <table class="summary">
        <tr>
            <td><strong>Total Nilai</strong></td>
            <td>{{ $interview_assessment->total_score }}</td>
            <td><strong>Rata-rata</strong></td>
            <td>{{ $interview_assessment->average_score }}</td>
        </tr>
        <tr>
            <td><strong>Persentase</strong></td>
            <td>{{ $interview_assessment->percentage }}%</td>
            <td><strong>Rekomendasi</strong></td>
            <td>{{ $interview_assessment->recommendation }}</td>
        </tr>
    </table>
    <p class="small">Skala: 5 = Sangat Baik, 4 = Baik, 3 = Sedang, 2 = Kurang, 1 = Sangat Kurang.</p>

    <div class="section-title">Kesimpulan / Catatan Akhir</div>
    <div class="notes">{{ $interview_assessment->hr_conclusion ?? 'Tidak ada catatan.' }}</div>

    <div class="signature">
        <div>Penilai</div>
        @if($interview_assessment->signature_path)
            <img src="{{ route('files.show', $interview_assessment->signature_path) }}" alt="Tanda tangan penilai" class="signature-image">
        @else
            <div class="signature-space"></div>
        @endif
        <strong>{{ $interview_assessment->hr_interviewer_name ?? '(...................................)' }}</strong>
    </div>

    @if(!empty($interview_assessment->photos))
        <div style="page-break-before: always; clear: both; padding-top: 20px;">
            <div class="section-title" style="text-align: center;">LAMPIRAN FOTO</div>
            <div style="margin-top: 15px; text-align: center;">
                @foreach($interview_assessment->photos as $photo)
                    <div style="margin-bottom: 20px; border: 1px solid #d1d5db; padding: 10px; display: inline-block;">
                        <img src="{{ route('files.show', $photo) }}" alt="Lampiran Foto" style="max-width: 700px; max-height: 500px; object-fit: contain;">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    </div>
</body>
</html>
