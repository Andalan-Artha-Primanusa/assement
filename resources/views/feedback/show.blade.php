<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Kandidat – {{ $feedback->assessment->template->name }}</title>
    <meta name="description" content="Form penilaian kandidat interview {{ $feedback->assessment->template->name }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #6f4933 0%, #b77a4b 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: white;
        }

        .header-card h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header-card p {
            font-size: 0.875rem;
            opacity: 0.85;
        }

        .header-card .template-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 0.25rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .candidate-info {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .candidate-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem 1.25rem;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            text-align: left;
        }

        .candidate-detail-label {
            display: block;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            opacity: 0.72;
        }

        .candidate-detail-value {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.82rem;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .candidate-details { grid-template-columns: 1fr; }
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .form-card-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
        }

        .form-card-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .form-card-header p {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .form-card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        input[type="text"],
        textarea {
            display: block;
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-family: inherit;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            transition: border-color 0.15s, box-shadow 0.15s;
            color: #111827;
            background: #fff;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #9a6745;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* Category block */
        .category-block {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .category-header {
            background: linear-gradient(135deg, #8a5a3b, #b77a4b);
            color: white;
            padding: 0.875rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .aspect-table {
            width: 100%;
            border-collapse: collapse;
        }

        .aspect-table thead {
            background: #f8fafc;
        }

        .aspect-table th {
            padding: 0.6rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e5e7eb;
        }

        .aspect-table th.center { text-align: center; }

        .aspect-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
        }

        .aspect-row:last-child { border-bottom: none; }

        .aspect-row:hover { background: #fafafa; }

        .aspect-row td {
            padding: 0.875rem 1rem;
            vertical-align: top;
        }

        .aspect-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: #1f2937;
        }

        .aspect-weight {
            font-size: 0.7rem;
            color: #8a5a3b;
            font-weight: 600;
            margin-top: 0.15rem;
        }

        /* Star rating */
        .star-rating {
            display: flex;
            gap: 0.3rem;
            justify-content: center;
            align-items: center;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 1.5rem;
            cursor: pointer;
            color: #d1d5db;
            transition: color 0.15s, transform 0.1s;
            margin: 0;
            line-height: 1;
            user-select: none;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label { /* no, reverse direction */ }

        .star-rating:hover label { color: #fbbf24; }
        .star-rating label:hover ~ label { color: #d1d5db; }

        .star-rating input[type="radio"]:checked ~ label { color: #d1d5db; }
        .star-rating input[type="radio"]:checked + label,
        .star-rating input[type="radio"]:checked + label ~ label { color: #d1d5db; }

        /* Simpler approach using :has or JavaScript */
        .score-group { text-align: center; }

        .score-buttons {
            display: flex;
            gap: 0.35rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .score-btn {
            width: 36px;
            height: 36px;
            border: 2px solid #e5e7eb;
            border-radius: 50%;
            background: white;
            font-size: 0.8rem;
            font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .score-btn:hover {
            border-color: #9a6745;
            color: #8a5a3b;
            background: #f5ebe3;
        }

        .score-btn.active {
            background: #8a5a3b;
            border-color: #8a5a3b;
            color: white;
            transform: scale(1.1);
        }

        .score-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.6rem;
            color: #9ca3af;
            margin-top: 0.25rem;
            padding: 0 0.25rem;
        }

        .notes-input {
            margin-top: 0.5rem;
        }

        .notes-input input {
            width: 100%;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            font-family: inherit;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f9fafb;
            color: #374151;
        }

        .notes-input input:focus {
            outline: none;
            border-color: #9a6745;
            background: white;
        }

        /* Alert */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert ul { margin-left: 1rem; margin-top: 0.25rem; }

        /* Submit */
        .submit-section {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-top: 1px solid #e2e8f0;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.78rem;
            color: #1d4ed8;
            margin-bottom: 1rem;
        }

        .scale-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .scale-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            color: #6b7280;
        }

        .scale-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #8a5a3b;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #8a5a3b, #b77a4b);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(99,102,241,0.4);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.5);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            padding-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header-card">
            <div class="template-badge">{{ $feedback->assessment->template->name }}</div>
            <h1>Form Penilaian Kandidat</h1>
            <p>Silakan isi penilaian kandidat berdasarkan hasil interview.</p>
            <div class="candidate-details">
                <div>
                    <span class="candidate-detail-label">Nama Kandidat</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->candidate_name ?: '-' }}</span>
                </div>
                <div>
                    <span class="candidate-detail-label">Posisi yang Dilamar</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->job_title ?: '-' }}</span>
                </div>
                <div>
                    <span class="candidate-detail-label">Penempatan</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->location ?: '-' }}</span>
                </div>
                <div>
                    <span class="candidate-detail-label">Tanggal Interview</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->interview_date?->format('d M Y') ?: 'Diisi pada form' }}</span>
                </div>
                <div>
                    <span class="candidate-detail-label">Interviewer HR</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->hr_interviewer_name ?: '-' }}</span>
                </div>
                <div>
                    <span class="candidate-detail-label">Interviewer User</span>
                    <span class="candidate-detail-value">{{ $feedback->assessment->user_interviewer_name ?: '-' }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('feedback.store', $feedback->token) }}">
            @csrf

            {{-- Identity --}}
            <div class="form-card">
                <div class="form-card-header">
                    <h2>Data Penilai</h2>
                    <p>Isi nama user/interviewer yang melakukan penilaian.</p>
                </div>
                <div class="form-card-body">
                    @if($errors->any())
                        <div class="alert alert-error">
                            <strong>Terdapat kesalahan:</strong>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="evaluator_name">Nama Penilai <span style="color:#ef4444">*</span></label>
                        <input type="text" id="evaluator_name" name="evaluator_name" value="{{ old('evaluator_name') }}" placeholder="Masukkan nama user/interviewer" required>
                    </div>

                    <div class="form-group">
                        <label for="interview_date">Tanggal Interview <span style="color:#ef4444">*</span></label>
                        <input type="date" id="interview_date" name="interview_date" value="{{ old('interview_date', $feedback->assessment->interview_date?->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            {{-- Scoring per Category --}}
            @foreach($feedback->assessment->template->categories as $category)
                <div class="form-card">
                    <div class="form-card-body" style="padding:0">
                        <div class="category-header">{{ $category->name }}</div>
                        <table class="aspect-table">
                            <thead>
                                <tr>
                                    <th style="width:2.5rem">No</th>
                                    <th>Aspek Penilaian</th>
                                    <th class="center" style="width:180px">Skor Diri (1–5)</th>
                                    <th style="width:200px">Catatan (opsional)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->aspects as $index => $aspect)
                                    <tr class="aspect-row">
                                        <td style="text-align:center;color:#9ca3af;font-size:0.8rem;font-weight:600">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="aspect-name">{{ $aspect->name }}</div>
                                            <div class="aspect-weight">Bobot: {{ $aspect->weight }}</div>
                                        </td>
                                        <td>
                                            <div class="score-group" x-data="{ score: {{ old("scores.{$aspect->id}.score", 0) }} }">
                                                <div class="score-buttons">
                                                    @for ($s = 1; $s <= 5; $s++)
                                                        <button type="button"
                                                            class="score-btn {{ old("scores.{$aspect->id}.score") == $s ? 'active' : '' }}"
                                                            data-score="{{ $s }}"
                                                            data-input="score_{{ $aspect->id }}"
                                                            onclick="selectScore(this, 'score_{{ $aspect->id }}', {{ $s }})">
                                                            {{ $s }}
                                                        </button>
                                                    @endfor
                                                </div>
                                                <div class="score-labels">
                                                    <span>Sangat Kurang</span>
                                                    <span>Sangat Baik</span>
                                                </div>
                                                <input type="hidden" name="scores[{{ $aspect->id }}][score]" id="score_{{ $aspect->id }}" value="{{ old("scores.{$aspect->id}.score", '') }}">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="notes-input">
                                                <input type="text" name="scores[{{ $aspect->id }}][notes]" value="{{ old("scores.{$aspect->id}.notes") }}" placeholder="Keterangan singkat...">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            {{-- General Feedback --}}
            <div class="form-card">
                <div class="form-card-header">
                    <h2>Komentar / Feedback Umum</h2>
                    <p>Tuliskan kesan, harapan, atau catatan umum Anda (opsional).</p>
                </div>
                <div class="form-card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <textarea id="feedback" name="feedback" placeholder="Tuliskan komentar atau harapan Anda di sini...">{{ old('feedback') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="form-card">
                <div class="submit-section">
                    <div class="info-box">
                        ⚠️ <strong>Perhatian:</strong> Form ini hanya bisa disubmit <strong>satu kali</strong>. Pastikan semua penilaian sudah benar sebelum mengirimkan.
                    </div>
                    <div class="scale-legend">
                        <span style="font-size:0.72rem;color:#6b7280;font-weight:600;margin-right:0.5rem">Skala Penilaian:</span>
                        @php $labels = ['Sangat Kurang','Kurang','Sedang','Baik','Sangat Baik']; @endphp
                        @foreach($labels as $i => $label)
                            <span class="scale-item">
                                <span class="scale-dot">{{ $i + 1 }}</span>
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                    <button type="submit" class="btn-submit" onclick="return confirmSubmit()">
                        ✓ Kirim Penilaian Kandidat
                    </button>
                </div>
            </div>
        </form>

        <div class="footer">
            &copy; {{ date('Y') }} &mdash; Form ini hanya berlaku untuk sesi ini.
        </div>
    </div>

    <script>
        function selectScore(btn, inputId, score) {
            // Update hidden input
            document.getElementById(inputId).value = score;

            // Update button styles
            const group = btn.closest('.score-buttons');
            group.querySelectorAll('.score-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // Restore active states on page load (for old() values)
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.score-buttons').forEach(function (group) {
                const inputId = group.querySelector('.score-btn').dataset.input;
                const hiddenInput = document.getElementById(inputId);
                if (hiddenInput && hiddenInput.value) {
                    const activeBtn = group.querySelector(`[data-score="${hiddenInput.value}"]`);
                    if (activeBtn) activeBtn.classList.add('active');
                }
            });
        });

        function confirmSubmit() {
            return confirm('Apakah Anda yakin ingin mengirimkan self-assessment ini? Form tidak bisa diubah setelah dikirim.');
        }
    </script>
</body>
</html>
