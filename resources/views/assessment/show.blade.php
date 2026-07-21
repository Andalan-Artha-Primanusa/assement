<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessment Mechanic') }}</h2>
            <div class="text-sm text-gray-500">
                <p>Mulai: {{ $assessment->started_at?->format('d M Y H:i') }}</p>
                <p>Selesai otomatis: {{ $assessment->ends_at?->format('d M Y H:i') }}</p>
            </div>
        </div>
    </x-slot>

    <style>
        .assessment-secure {
            -webkit-user-select: none;
            user-select: none;
        }

        .assessment-watermark {
            background-image: repeating-linear-gradient(
                -28deg,
                rgba(79, 70, 229, 0.06) 0,
                rgba(79, 70, 229, 0.06) 1px,
                transparent 1px,
                transparent 120px
            );
        }

        #screenshot-overlay, #assessment-content {
            transition: opacity 0.05s;
            will-change: opacity;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            body::before {
                content: "Assessment tidak dapat dicetak.";
                visibility: visible !important;
                display: block;
                padding: 48px;
                font: 600 20px sans-serif;
            }
        }
    </style>

    <div class="assessment-secure assessment-watermark py-6 sm:py-12" id="assessment-page">
        <div id="screenshot-overlay" class="fixed inset-0 z-50 flex items-center justify-center bg-black" style="opacity:0;pointer-events:none;will-change:opacity">
            <p class="text-2xl font-bold text-white">Dilarang mengambil screenshot!</p>
        </div>
        <div id="assessment-content" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="security-lock-message" class="mb-6 hidden rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                Assessment dikunci karena sistem mendeteksi aktivitas di luar halaman tes. Minta admin untuk membuka akses.
            </div>

            <div class="mb-6 grid gap-4 bg-white p-4 sm:p-5 shadow-sm sm:rounded-lg md:grid-cols-[220px_1fr]">
                <div class="overflow-hidden rounded-md bg-gray-900">
                    <video id="camera-preview" class="aspect-video h-full w-full object-cover" autoplay muted playsinline></video>
                </div>
                <div class="flex flex-col justify-center">
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="camera-badge" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Kamera belum aktif</span>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                            Pelanggaran: {{ $assessment->security_violations }}/{{ config('assessment.max_security_blocks') }}
                        </span>
                    </div>
                    <p id="camera-status" class="mt-3 text-xs sm:text-sm text-gray-600">
                        Kamera wajib aktif selama assessment. Jika kamera mati, pindah tab, atau membuka halaman lain, sistem akan mencatat pelanggaran.
                    </p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">
                        Sisa waktu: <span id="countdown-timer">--:--:--</span>
                    </p>
                    <button type="button" id="start-camera" class="mt-4 w-full sm:w-fit rounded-md bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-black min-h-[44px]">
                        Nyalakan Kamera
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('assessment.submit', $assessment) }}" class="pointer-events-none space-y-5 opacity-40" id="assessment-form">
                @csrf

                @foreach ($assessment->answers as $answer)
                    @php($question = $answer->question)
                    <div class="bg-white p-4 sm:p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start gap-3 sm:gap-4">
                            <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs sm:text-sm font-semibold text-indigo-700">
                                {{ $answer->position }}
                            </div>
                            <div class="w-full min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">{{ ucfirst($question->difficulty) }}</span>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                                <div class="mt-5 grid gap-3">
                                    @foreach (['a', 'b', 'c', 'd'] as $option)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-200 p-3 sm:p-4 hover:border-indigo-300 hover:bg-indigo-50 min-h-[44px]">
                                            <input type="radio" name="answers[{{ $answer->id }}]" value="{{ $option }}" class="mt-0.5 sm:mt-1 shrink-0 border-gray-300 text-indigo-600 focus:ring-indigo-500" required @checked($answer->selected_option === $option)>
                                            <span class="text-sm sm:text-base">
                                                <span class="font-semibold uppercase text-gray-900">{{ $option }}.</span>
                                                <span class="text-gray-700">{{ $question->optionText($option) }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="sticky bottom-0 z-40 -mx-4 mt-6 border-t border-gray-200 bg-white px-4 py-4 shadow-lg sm:static sm:mt-0 sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                    <button class="w-full rounded-md bg-indigo-600 px-5 py-4 text-base font-bold text-white shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto sm:py-3 sm:text-sm" id="submit-assessment">
                        Kirim Jawaban
                    </button>
                </div>
            </form>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const violationUrl = @json(route('assessment.security-violation', $assessment));
            const submitUrl = @json(route('assessment.submit', $assessment));
            const csrfToken = @json(csrf_token());
            const endsAt = new Date(@json($assessment->ends_at?->toIso8601String()));
            const lockMessage = document.getElementById('security-lock-message');
            const screenshotOverlay = document.getElementById('screenshot-overlay');
            const form = document.getElementById('assessment-form');
            const cameraPreview = document.getElementById('camera-preview');
            const cameraBadge = document.getElementById('camera-badge');
            const cameraStatus = document.getElementById('camera-status');
            const startCameraButton = document.getElementById('start-camera');
            const countdownTimer = document.getElementById('countdown-timer');
            let armed = false;
            let locked = false;
            let submitting = false;
            let cameraReady = false;
            let cameraStarted = false;
            let cameraPromptOpen = false;

            setTimeout(() => {
                armed = true;
            }, 1500);

            const enableAssessment = () => {
                form.classList.remove('pointer-events-none', 'opacity-40');
                cameraBadge.className = 'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';
                cameraBadge.textContent = 'Kamera aktif';
                cameraStatus.textContent = 'Kamera aktif. Tetap berada di halaman assessment sampai jawaban dikirim.';
            };

            const lockScreen = () => {
                locked = true;
                form.classList.add('pointer-events-none', 'opacity-30', 'blur-sm');
                lockMessage.classList.remove('hidden');
            };

            const contentContainer = document.getElementById('assessment-content');

            const showScreenshotOverlay = () => {
                screenshotOverlay.style.opacity = '1';
                screenshotOverlay.style.pointerEvents = 'auto';
                contentContainer.style.opacity = '0';
            };

            const hideScreenshotOverlay = () => {
                screenshotOverlay.style.opacity = '0';
                screenshotOverlay.style.pointerEvents = 'none';
                contentContainer.style.opacity = '1';
            };

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    showScreenshotOverlay();
                    reportViolation('Peserta meninggalkan tab assessment.', true);
                } else {
                    hideScreenshotOverlay();
                }
            });

            window.addEventListener('blur', () => {
                showScreenshotOverlay();
                reportViolation('Jendela assessment kehilangan fokus.');
            });

            window.addEventListener('focus', hideScreenshotOverlay);

            window.addEventListener('beforeunload', () => {
                reportViolation('Peserta membuka halaman lain saat assessment berlangsung.', true);
            });

            const appendSelectedAnswers = (payload) => {
                document.querySelectorAll('input[type="radio"]:checked').forEach((input) => {
                    payload.append(input.name, input.value);
                });
            };

            const submitBecauseTimeExpired = () => {
                if (submitting) {
                    return;
                }

                submitting = true;

                const payload = new FormData();
                payload.append('_token', csrfToken);
                appendSelectedAnswers(payload);

                fetch(submitUrl, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin',
                    keepalive: true,
                }).finally(() => {
                    window.location.href = @json(route('assessment.result', $assessment));
                });
            };

            const updateCountdown = () => {
                const remaining = Math.max(0, endsAt.getTime() - Date.now());
                const totalSeconds = Math.floor(remaining / 1000);
                const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');

                countdownTimer.textContent = `${hours}:${minutes}:${seconds}`;

                if (remaining <= 0) {
                    submitBecauseTimeExpired();
                }
            };

            const reportViolation = (reason, useBeacon = false) => {
                if (!armed || locked || submitting || cameraPromptOpen) {
                    return;
                }

                lockScreen();

                const payload = new FormData();
                payload.append('_token', csrfToken);
                payload.append('reason', reason);
                appendSelectedAnswers(payload);

                if (useBeacon && navigator.sendBeacon) {
                    navigator.sendBeacon(violationUrl, payload);
                    setTimeout(() => {
                        window.location.reload();
                    }, 900);
                } else {
                    fetch(violationUrl, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin',
                        keepalive: true,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                                return;
                            }

                            window.location.reload();
                        })
                        .catch(() => window.location.reload());
                }
            };

            const requestCamera = async () => {
                if (!navigator.mediaDevices?.getUserMedia) {
                    cameraStatus.textContent = 'Browser tidak mendukung akses kamera. Gunakan browser modern untuk assessment.';
                    return;
                }

                cameraPromptOpen = true;

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 640 },
                            height: { ideal: 360 },
                            facingMode: 'user',
                        },
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
            updateCountdown();
            setInterval(updateCountdown, 1000);

            setInterval(() => {
                if (cameraStarted && !cameraReady) {
                    reportViolation('Kamera assessment tidak aktif.');
                }
            }, 3000);

            document.addEventListener('contextmenu', (event) => event.preventDefault());
            document.addEventListener('copy', (event) => event.preventDefault());
            document.addEventListener('cut', (event) => event.preventDefault());
            document.addEventListener('dragstart', (event) => event.preventDefault());

            document.addEventListener('keydown', (event) => {
                const key = event.key.toLowerCase();
                const isPrintScreen = event.key === 'PrintScreen';
                const isBlockedShortcut = (event.ctrlKey || event.metaKey) && ['p', 's', 'u'].includes(key);

                if (isPrintScreen) {
                    event.preventDefault();
                    showScreenshotOverlay();
                    reportViolation('Percobaan screenshot terdeteksi.');
                    setTimeout(hideScreenshotOverlay, 500);
                }

                if (isBlockedShortcut) {
                    event.preventDefault();
                    reportViolation('Shortcut terlarang digunakan.');
                }
            });

            form.addEventListener('submit', (event) => {
                if (!cameraReady) {
                    event.preventDefault();
                    alert('Kamera wajib aktif sebelum mengirim jawaban.');
                    return;
                }

                if (!confirm('Kirim jawaban sekarang?')) {
                    event.preventDefault();
                    return;
                }

                submitting = true;
            });
        })();
    </script>
</x-app-layout>
