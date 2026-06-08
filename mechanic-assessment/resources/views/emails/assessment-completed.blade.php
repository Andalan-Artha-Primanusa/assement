<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .body { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .btn { display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 6px; }
        .score { font-size: 24px; font-weight: bold; color: #6366f1; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Screening Mechanic</h2>
        </div>
        <div class="body">
            <p>Halo Admin,</p>
            <p>Peserta <strong>{{ $user->name }}</strong> telah menyelesaikan assessment.</p>
            <table style="width:100%; border-collapse: collapse;">
                <tr><td style="padding:8px; border-bottom:1px solid #e5e7eb;">Nama</td><td style="padding:8px; border-bottom:1px solid #e5e7eb;">{{ $user->name }}</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #e5e7eb;">Email</td><td style="padding:8px; border-bottom:1px solid #e5e7eb;">{{ $user->email }}</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #e5e7eb;">Benar</td><td style="padding:8px; border-bottom:1px solid #e5e7eb;">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #e5e7eb;">Nilai</td><td class="score">{{ number_format($assessment->score, 2) }}</td></tr>
            </table>
            <p style="text-align:center; margin-top:20px;">
                <a href="{{ $resultUrl }}" class="btn">Lihat Detail</a>
            </p>
        </div>
    </div>
</body>
</html>
