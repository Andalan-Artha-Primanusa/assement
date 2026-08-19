<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessment') }}</h2>
            <div class="flex items-center gap-3">
                <span id="countdown" class="rounded-md bg-rose-100 px-3 py-1.5 text-sm font-bold text-rose-700 tabular-nums">--:--</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @php
                $secureMode = $currentSegment->type !== 'upload';
                $segmentLabel = match ($currentSegment->type) {
                    'multiple_choice' => 'PG (Pilihan Ganda)',
                    'essay' => 'Essay',
                    'upload' => 'Portfolio / Upload Hasil',
                    default => ucfirst($currentSegment->type),
                };
            @endphp

            {{-- Segment Progress --}}
            <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700">Segment: <span class="text-indigo-600">{{ $segmentLabel }}</span></p>
                    <p class="text-xs text-gray-500">Sisa waktu segment: <span id="segment-countdown" class="font-bold text-rose-600">--:--</span></p>
                </div>
                <div class="flex gap-2">
                    @foreach ($assessment->segments as $seg)
                        <div class="flex-1 rounded-md p-2 text-center text-xs font-semibold
                            {{ $seg->isCompleted() ? 'bg-emerald-100 text-emerald-700' : ($seg->isInProgress() ? 'bg-indigo-100 text-indigo-700 ring-2 ring-indigo-400' : 'bg-gray-100 text-gray-400') }}">
                            {{ $seg->type === 'multiple_choice' ? 'PG' : ucfirst($seg->type) }}
                            @if ($seg->isCompleted())
                                <svg class="inline h-3 w-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Security Warning --}}
            <div id="security-warning" class="mb-4 hidden rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <span id="security-message"></span>
                <span class="font-semibold"> Sisa percobaan: <span id="violations-count">{{ config('assessment.max_security_blocks', 2) - ($assessment->security_violations ?? 0) }}</span></span>
            </div>

            @if ($secureMode)
                <div class="mb-6 grid gap-4 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-[220px_1fr]">
                    <div class="overflow-hidden rounded-md bg-gray-900">
                        <video id="camera-preview" class="aspect-video h-full w-full object-cover" autoplay muted playsinline></video>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="flex flex-wrap items-center gap-2">
                            <span id="camera-badge" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Kamera belum aktif</span>
                            <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Mode ketat</span>
                        </div>
                        <p id="camera-status" class="mt-3 text-sm text-gray-600">
                            Kamera wajib aktif. Pada segmen PG dan Essay, pindah tab atau membuka halaman lain akan mengunci assessment.
                        </p>
                        <button type="button" id="start-camera" class="mt-4 w-full rounded-md bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-black sm:w-fit">
                            Nyalakan Kamera
                        </button>
                    </div>
                </div>
            @else
                <div class="mb-6 rounded-md border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
                    <strong>Mode upload portfolio:</strong> peserta boleh membuka folder, memilih file, atau berpindah tab untuk mengambil dokumen pendukung. Aktivitas ini tidak akan memicu blokir.
                </div>
            @endif

            {{-- Questions --}}
            <form id="assessment-form" method="POST" action="{{ route('assessment.submit', $assessment) }}" enctype="multipart/form-data" class="{{ $secureMode ? 'pointer-events-none opacity-40' : '' }}" data-secure-mode="{{ $secureMode ? '1' : '0' }}" data-popup-skip>
                @csrf
                <div class="space-y-4">
                    @foreach ($segmentAnswers as $answer)
                        <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ $answer->position }}</span>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $answer->question->text }}</p>
                                    @if ($answer->question->image)
                                        <img src="{{ $answer->question->imageUrl() }}" alt="Gambar soal" class="mt-3 max-h-64 rounded-md border border-gray-200 object-contain">
                                    @endif

                                    @if ($answer->question->isAutoScored())
                                        <div class="mt-4 space-y-2">
                                            @foreach ($answer->question->answerOptions() as $opt)
                                                @if ($answer->question->{'option_'.$opt})
                                                    <label class="flex items-center gap-3 rounded-md border border-gray-200 p-3 cursor-pointer hover:bg-gray-50 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50 transition">
                                                        <input type="radio" name="answers[{{ $answer->id }}]" value="{{ $opt }}" class="text-indigo-600 focus:ring-indigo-500" @checked(old('answers.'.$answer->id, $answer->selected_option) === $opt)>
                                                        <span class="text-sm text-gray-700">{{ strtoupper($opt) }}. {{ $answer->question->{'option_'.$opt} }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif ($answer->question->isEssay())
                                        <div class="mt-4">
                                            <textarea name="answers[{{ $answer->id }}]" rows="4" placeholder="Tulis jawaban essay di sini..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('answers.'.$answer->id, $answer->answer_text) }}</textarea>
                                        </div>
                                    @elseif ($answer->question->isUpload())
                                        <div class="mt-4">
                                            <input type="file" name="answers[{{ $answer->id }}]" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                                            <p class="mt-1 text-xs text-gray-500">Format: PDF, gambar, dokumen (max 10MB).</p>
                                            @if ($answer->file_path)
                                                <p class="mt-1 text-xs text-emerald-600">File sudah diupload. Upload baru akan menggantikan yang lama.</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="submit" class="rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 min-h-[44px]">
                        {{ $assessment->segments()->where('status', 'completed')->count() === $assessment->segments()->count() - 1 ? 'Selesai & Kirim' : 'Selesai Segment Ini' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const segmentRemaining = {{ $currentSegment->remainingSeconds() }};
            const overallRemaining = {{ $assessment->ends_at ? max(0, $assessment->ends_at->timestamp - now()->timestamp) : 999999 }};
            const maxViolations = {{ config('assessment.max_security_blocks', 2) }};
            const secureMode = @json($secureMode);
            const isOperator = {{ $assessment->questionPackage?->type === \App\Models\QuestionPackage::TYPE_OPERATOR ? 'true' : 'false' }};
            const form = document.getElementById('assessment-form');
            const cameraPreview = document.getElementById('camera-preview');
            const cameraBadge = document.getElementById('camera-badge');
            const cameraStatus = document.getElementById('camera-status');
            const startCameraButton = document.getElementById('start-camera');
            let cameraReady = !secureMode;
            let cameraStarted = false;
            let cameraPromptOpen = false;
            let violations = {{ $assessment->security_violations ?? 0 }};
            let armed = false;
            let locked = false;
            let manualPromptOpen = false;

            setTimeout(() => { armed = true; }, 1500);

            let segLeft = segmentRemaining;
            let overallLeft = overallRemaining;
            const segEl = document.getElementById('segment-countdown');
            const overallEl = document.getElementById('countdown');

            const interval = setInterval(function () {
                segLeft = Math.max(0, segLeft - 1);
                overallLeft = Math.max(0, overallLeft - 1);

                const segM = Math.floor(segLeft / 60);
                const segS = segLeft % 60;
                segEl.textContent = String(segM).padStart(2, '0') + ':' + String(segS).padStart(2, '0');

                const oM = Math.floor(overallLeft / 60);
                const oS = overallLeft % 60;
                overallEl.textContent = String(oM).padStart(2, '0') + ':' + String(oS).padStart(2, '0');

                if (segLeft <= 0 || overallLeft <= 0) {
                    clearInterval(interval);
                    form.dataset.submitting = '1';
                    form.dataset.confirmed = '1';
                    form.requestSubmit();
                }

                if (segLeft <= 60) {
                    segEl.classList.add('text-red-700');
                    segEl.classList.remove('text-rose-600');
                }
                if (overallLeft <= 120) {
                    overallEl.classList.add('bg-red-100', 'text-red-700');
                    overallEl.classList.remove('bg-rose-100', 'text-rose-700');
                }
            }, 1000);

            if (secureMode) {
                const enableAssessment = () => {
                    form.classList.remove('pointer-events-none', 'opacity-40');
                    cameraBadge.className = 'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';
                    cameraBadge.textContent = 'Kamera aktif';
                    cameraStatus.textContent = 'Kamera aktif. Tetap berada di halaman assessment sampai segmen selesai.';
                };

                const requestCamera = async () => {
                    if (!navigator.mediaDevices?.getUserMedia) {
                        cameraStatus.textContent = 'Browser tidak mendukung akses kamera. Gunakan browser modern untuk assessment.';
                        return;
                    }

                    cameraPromptOpen = true;

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { width: { ideal: 640 }, height: { ideal: 360 }, facingMode: 'user' },
                            audio: false,
                        });

                        cameraPreview.srcObject = stream;
                        cameraReady = true;
                        cameraStarted = true;
                        enableAssessment();

                        stream.getVideoTracks().forEach((track) => {
                            track.addEventListener('ended', () => {
                                cameraReady = false;
                                reportViolation('Kamera assessment mati atau terputus.');
                            });
                        });
                    } catch (error) {
                        cameraReady = false;
                        cameraStatus.textContent = 'Kamera belum bisa aktif. Izinkan akses kamera untuk mulai mengerjakan soal.';
                    } finally {
                        cameraPromptOpen = false;
                    }
                };

                startCameraButton.addEventListener('click', requestCamera);
                requestCamera();

                document.addEventListener('contextmenu', e => e.preventDefault());
                document.addEventListener('copy', e => e.preventDefault());
                document.addEventListener('cut', e => e.preventDefault());
                document.addEventListener('dragstart', e => e.preventDefault());

                document.addEventListener('keydown', (event) => {
                    const key = event.key.toLowerCase();
                    const isBlockedShortcut = (event.ctrlKey || event.metaKey) && ['p', 's', 'u'].includes(key);

                    if (event.key === 'PrintScreen' || isBlockedShortcut) {
                        event.preventDefault();
                        reportViolation(event.key === 'PrintScreen' ? 'Percobaan screenshot terdeteksi.' : 'Shortcut terlarang digunakan.');
                    }
                });

                document.addEventListener('visibilitychange', function () {
                    if (document.hidden && isOperator) {
                        reportViolation('Peserta meninggalkan tab assessment.');
                    }
                });

                // window.addEventListener('blur', () => {
                //     reportViolation('Jendela assessment kehilangan fokus.');
                // });

                setInterval(() => {
                    if (cameraStarted && !cameraReady) {
                        reportViolation('Kamera assessment tidak aktif.');
                    }
                }, 3000);
            }

            form.addEventListener('submit', async function (event) {
                if (form.dataset.confirmed === '1') {
                    delete form.dataset.confirmed;
                    form.dataset.submitting = '1';
                    return;
                }

                event.preventDefault();

                if (secureMode && !cameraReady) {
                    window.appNotify?.({
                        type: 'warning',
                        title: 'Kamera belum aktif',
                        message: 'Kamera wajib aktif sebelum menyelesaikan segmen ini.',
                    });
                    return;
                }

                const buttonLabel = event.submitter?.textContent?.trim() || 'Kirim jawaban';
                const isFinalSubmit = buttonLabel.includes('Selesai & Kirim');
                const title = isFinalSubmit ? 'Kirim assessment sekarang?' : 'Selesaikan segmen ini?';
                const message = isFinalSubmit
                    ? 'Pastikan semua jawaban final sudah benar. Setelah dikirim, assessment tidak bisa diedit lagi.'
                    : 'Jawaban segmen ini akan disimpan dan kamu lanjut ke segmen berikutnya.';
                const confirmText = isFinalSubmit ? 'Ya, kirim assessment' : 'Ya, lanjut segmen';
                let confirmed = false;

                manualPromptOpen = true;
                try {
                    confirmed = window.appConfirm
                        ? await window.appConfirm({ title, message, confirmText })
                        : window.confirm(`${title}\n\n${message}`);
                } finally {
                    setTimeout(() => { manualPromptOpen = false; }, 300);
                }

                if (!confirmed) {
                    return;
                }

                if (window.AppPopup?.refreshCsrfToken) {
                    const refreshed = await window.AppPopup.refreshCsrfToken();

                    if (!refreshed) {
                        return;
                    }
                }

                form.dataset.confirmed = '1';
                form.requestSubmit();
            });

            function reportViolation(reason) {
                if (!secureMode || !armed || locked || cameraPromptOpen || manualPromptOpen || form.dataset.submitting === '1') {
                    return;
                }

                locked = true;
                const payload = JSON.stringify({ reason, answers: collectAnswers() });

                fetch('{{ route("assessment.security-violation", $assessment) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: payload,
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.csrf_expired && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }

                        if (data.submitted) {
                            window.location.href = data.redirect;
                            return;
                        }

                        if (data.blocked) {
                            window.location.reload();
                            return;
                        }

                        violations = data.violations || violations + 1;
                        const remaining = maxViolations - violations;
                        document.getElementById('violations-count').textContent = Math.max(0, remaining);
                        document.getElementById('security-message').textContent = 'Aktivitas di luar halaman assessment terdeteksi.';
                        document.getElementById('security-warning').classList.remove('hidden');
                        setTimeout(() => { locked = false; }, 3000);
                    })
                    .catch(() => { locked = false; });
            }

            function collectAnswers() {
                const answers = {};

                document.querySelectorAll('#assessment-form input[type="radio"]:checked, #assessment-form textarea').forEach(el => {
                    const id = el.name.match(/\d+/)?.[0];
                    if (id) {
                        answers[id] = el.value || '';
                    }
                });

                return answers;
            }
        })();
    </script>
</x-app-layout>
