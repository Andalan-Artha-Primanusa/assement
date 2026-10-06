<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Terkirim – Terima Kasih!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.2);
            padding: 3rem 2rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(16,185,129,0.35);
        }

        .icon svg { width: 40px; height: 40px; color: white; }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.75rem;
        }

        p {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.6;
        }

        .summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            text-align: left;
        }

        .summary-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 0.75rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row:last-child { border-bottom: none; }

        .summary-label { color: #6b7280; font-weight: 500; }
        .summary-value { font-weight: 700; color: #111827; }

        .submitted-notice {
            font-size: 0.78rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }

        @if($feedback->isSubmitted() && !$feedback->submitted_at)
        /* already handled */
        @endif
    </style>
</head>
<body>
    <div class="card">
        @if(session('success'))
            {{-- Freshly submitted --}}
            <div class="icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1>Terima kasih, {{ $feedback->candidate_name ?? 'Kandidat' }}!</h1>
            <p>Self-assessment Anda telah berhasil dikirimkan. Tim HR akan meninjau hasil penilaian Anda.</p>

            @if($feedback->submitted_at)
                <div class="summary">
                    <div class="summary-title">Ringkasan Penilaian Anda</div>
                    <div class="summary-row">
                        <span class="summary-label">Template</span>
                        <span class="summary-value">{{ $feedback->assessment->template->name }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Skor</span>
                        <span class="summary-value">{{ $feedback->total_score }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Rata-rata</span>
                        <span class="summary-value">{{ $feedback->average_score }} / 5</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Persentase</span>
                        <span class="summary-value">{{ $feedback->percentage }}%</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Waktu Submit</span>
                        <span class="summary-value">{{ $feedback->submitted_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            @endif

            <p class="submitted-notice">Anda dapat menutup halaman ini.</p>

        @else
            {{-- Already submitted before (accessed link again) --}}
            <div class="icon" style="background: linear-gradient(135deg, #6366f1, #818cf8);">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>Feedback Sudah Dikirim</h1>
            <p>Self-assessment untuk form ini sudah pernah disubmit pada <strong>{{ $feedback->submitted_at?->format('d M Y, H:i') }}</strong>. Anda tidak dapat mengisi ulang form yang sama.</p>

            @if($feedback->submitted_at)
                <div class="summary">
                    <div class="summary-title">Ringkasan Penilaian Sebelumnya</div>
                    <div class="summary-row">
                        <span class="summary-label">Nama</span>
                        <span class="summary-value">{{ $feedback->candidate_name ?? '-' }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Skor</span>
                        <span class="summary-value">{{ $feedback->total_score }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Persentase</span>
                        <span class="summary-value">{{ $feedback->percentage }}%</span>
                    </div>
                </div>
            @endif

            <p class="submitted-notice">Jika ada kesalahan, hubungi tim HR untuk mendapatkan link baru.</p>
        @endif
    </div>
</body>
</html>
