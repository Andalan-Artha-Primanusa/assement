<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat - {{ $assessment->user->name }}</title>
    @include('partials.favicon')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 2rem;
        }

        .certificate-wrapper {
            width: 1000px;
            background: #fff;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            border-radius: 4px;
            overflow: hidden;
        }

        .certificate {
            position: relative;
            padding: 0;
            background: linear-gradient(135deg, #fefefe 0%, #fafbfc 100%);
        }

        /* Decorative top bar */
        .top-bar {
            height: 8px;
            background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 25%, #1e3a5f 50%, #2563eb 75%, #1e3a5f 100%);
        }

        .certificate-inner {
            padding: 50px 70px 40px;
            position: relative;
        }

        /* Corner decorations */
        .corner { position: absolute; width: 80px; height: 80px; }
        .corner-tl { top: 20px; left: 20px; border-top: 3px solid #1e3a5f; border-left: 3px solid #1e3a5f; }
        .corner-tr { top: 20px; right: 20px; border-top: 3px solid #1e3a5f; border-right: 3px solid #1e3a5f; }
        .corner-bl { bottom: 20px; left: 20px; border-bottom: 3px solid #1e3a5f; border-left: 3px solid #1e3a5f; }
        .corner-br { bottom: 20px; right: 20px; border-bottom: 3px solid #1e3a5f; border-right: 3px solid #1e3a5f; }

        /* Watermark pattern */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: 800;
            color: rgba(37, 99, 235, 0.03);
            white-space: nowrap;
            pointer-events: none;
            font-family: 'Playfair Display', serif;
            letter-spacing: 20px;
        }

        .header { text-align: center; margin-bottom: 10px; position: relative; z-index: 1; }

        .company-name {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 20px;
        }

        .certificate-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 800;
            color: #1e3a5f;
            letter-spacing: 3px;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .certificate-subtitle {
            font-size: 14px;
            font-weight: 400;
            color: #94a3b8;
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .divider {
            width: 120px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #2563eb, transparent);
            margin: 25px auto;
        }

        .body { text-align: center; position: relative; z-index: 1; }

        .presented-to {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 10px;
        }

        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 40px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .recipient-email {
            font-size: 13px;
            font-weight: 400;
            color: #94a3b8;
            margin-bottom: 25px;
        }

        .description {
            font-size: 15px;
            font-weight: 400;
            color: #475569;
            line-height: 1.8;
            max-width: 650px;
            margin: 0 auto 10px;
        }

        .description strong {
            color: #1e3a5f;
            font-weight: 600;
        }

        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 12px 30px;
            border-radius: 50px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .score-badge .label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
        }

        .score-badge .value {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }

        .grade-badge {
            display: inline-block;
            padding: 6px 24px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 5px;
        }

        .grade-lolos {
            background: #ecfdf5;
            color: #047857;
            border: 2px solid #a7f3d0;
        }

        .grade-dipertimbangkan {
            background: #fffbeb;
            color: #b45309;
            border: 2px solid #fde68a;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0 25px;
            padding: 20px 30px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .detail-item { text-align: center; }
        .detail-item .label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .detail-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            position: relative;
            z-index: 1;
        }

        .signature-block { text-align: center; width: 200px; }
        .signature-line {
            width: 100%;
            height: 1px;
            background: #1e293b;
            margin-bottom: 8px;
        }
        .signature-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .signature-title {
            font-size: 11px;
            font-weight: 400;
            color: #94a3b8;
        }

        .cert-id {
            text-align: center;
            font-size: 10px;
            font-weight: 500;
            color: #cbd5e1;
            letter-spacing: 2px;
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }

        .bottom-bar {
            height: 4px;
            background: linear-gradient(90deg, #1e3a5f, #2563eb, #1e3a5f);
        }

        /* Print button */
        .print-controls {
            text-align: center;
            padding: 20px;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-print:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.4); }

        @media print {
            body { background: #fff; padding: 0; }
            .certificate-wrapper { box-shadow: none; border-radius: 0; }
            .print-controls { display: none !important; }
        }
    </style>
</head>
<body>
    <div>
        <div class="certificate-wrapper">
            <div class="certificate">
                <div class="top-bar"></div>
                <div class="certificate-inner">
                    <div class="corner corner-tl"></div>
                    <div class="corner corner-tr"></div>
                    <div class="corner corner-bl"></div>
                    <div class="corner corner-br"></div>
                    <div class="watermark">CERTIFICATE</div>

                    <div class="header">
                        <p class="company-name">Andalan Artha Primanusa</p>
                        <h1 class="certificate-title">Sertifikat</h1>
                        <p class="certificate-subtitle">Kelulusan Assessment</p>
                    </div>

                    <div class="divider"></div>

                    <div class="body">
                        <p class="presented-to">Diberikan Kepada</p>
                        <h2 class="recipient-name">{{ $assessment->user->name }}</h2>
                        <p class="recipient-email">{{ $assessment->user->email }}</p>

                        <p class="description">
                            Telah berhasil menyelesaikan assessment
                            <strong>{{ $package->name ?? 'Screening Assessment' }}</strong>
                            @if ($package?->level)
                                untuk level <strong>{{ $package->level }}</strong>
                            @endif
                            dengan hasil sebagai berikut:
                        </p>

                        <div class="score-badge">
                            <span class="label">Nilai</span>
                            <span class="value">{{ number_format($assessment->score, 2) }}</span>
                        </div>

                        <br>

                        @if ($grade)
                            <span class="grade-badge {{ $grade === 'Lolos' ? 'grade-lolos' : 'grade-dipertimbangkan' }}">
                                {{ $grade }}
                            </span>
                        @endif

                        <div class="details-grid">
                            <div class="detail-item">
                                <p class="label">Tanggal</p>
                                <p class="value">{{ $assessment->submitted_at?->format('d M Y') }}</p>
                            </div>
                            <div class="detail-item">
                                <p class="label">Jawaban Benar</p>
                                <p class="value">{{ $assessment->correct_answers }} / {{ $assessment->total_questions }}</p>
                            </div>
                            <div class="detail-item">
                                <p class="label">Tipe</p>
                                <p class="value">{{ ucfirst($package?->type ?? '-') }}{{ $package?->level ? ' - '.$package->level : '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="footer">
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <p class="signature-name">Admin HR</p>
                            <p class="signature-title">Andalan Artha Primanusa</p>
                        </div>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <p class="signature-name">{{ $assessment->user->name }}</p>
                            <p class="signature-title">Peserta</p>
                        </div>
                    </div>

                    <p class="cert-id">No. {{ $certificateNumber }}</p>
                </div>
                <div class="bottom-bar"></div>
            </div>
        </div>

        <div class="print-controls">
            <button onclick="window.print()" class="btn-print">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Sertifikat
            </button>
        </div>
    </div>
</body>
</html>
