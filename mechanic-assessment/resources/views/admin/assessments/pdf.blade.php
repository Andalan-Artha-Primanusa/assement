<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hasil Assessment - {{ $assessment->user->name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #6366f1; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 0; color: #1f2937; }
        .header p { margin: 5px 0 0; color: #6b7280; font-size: 11px; }
        .score-box { text-align: center; padding: 20px; margin-bottom: 20px; background: #f0fdf4; border-radius: 8px; }
        .score-box .score { font-size: 36px; font-weight: bold; color: #16a34a; }
        .score-box .label { font-size: 11px; color: #6b7280; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 6px 10px; border: 1px solid #e5e7eb; }
        .info-table td:first-child { font-weight: 600; background: #f9fafb; width: 140px; }
        .answers-table { width: 100%; border-collapse: collapse; }
        .answers-table th { background: #f3f4f6; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; border: 1px solid #e5e7eb; }
        .answers-table td { padding: 6px 10px; border: 1px solid #e5e7eb; font-size: 11px; }
        .correct { color: #16a34a; font-weight: bold; }
        .wrong { color: #dc2626; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Screening Mechanic</h1>
        <p>Hasil Assessment</p>
    </div>

    <div class="score-box">
        <div class="score">{{ number_format($assessment->score, 2) }}</div>
        <div class="label">Nilai Akhir</div>
    </div>

    <table class="info-table">
        <tr><td>Peserta</td><td>{{ $assessment->user->name }}</td></tr>
        <tr><td>Email</td><td>{{ $assessment->user->email }}</td></tr>
        <tr><td>Paket Soal</td><td>{{ $assessment->questionPackage?->name ?? '-' }}</td></tr>
        <tr><td>Tanggal</td><td>{{ $assessment->submitted_at?->format('d M Y H:i') }}</td></tr>
        <tr><td>Benar</td><td>{{ $assessment->correct_answers }} / {{ $assessment->total_questions }}</td></tr>
        <tr><td>Durasi</td><td>{{ $assessment->duration_minutes }} menit</td></tr>
    </table>

    @if ($assessment->answers->isNotEmpty())
        <h3 style="margin-bottom: 8px;">Detail Jawaban</h3>
        <table class="answers-table">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Soal</th>
                    <th style="width:50px">Jawab</th>
                    <th style="width:50px">Benar</th>
                    <th style="width:40px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assessment->answers as $answer)
                    <tr>
                        <td>{{ $answer->position }}</td>
                        <td>{{ $answer->question->text }}</td>
                        <td>{{ $answer->selected_option ? strtoupper($answer->selected_option) : '-' }}</td>
                        <td>{{ strtoupper($answer->question->correct_option) }}</td>
                        <td class="{{ $answer->is_correct ? 'correct' : 'wrong' }}">{{ $answer->is_correct ? '✓' : '✗' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Dicetak pada {{ now()->format('d M Y H:i') }} | Screening Mechanic
    </div>
</body>
</html>
