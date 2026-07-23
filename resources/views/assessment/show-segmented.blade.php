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

            {{-- Segment Progress --}}
            <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700">Segment: <span class="text-indigo-600">{{ $currentSegment->type === 'multiple_choice' ? 'PG (Multiple Choice)' : ucfirst($currentSegment->type) }}</span></p>
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

            {{-- Questions --}}
            <form id="assessment-form" method="POST" action="{{ route('assessment.submit', $assessment) }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    @foreach ($segmentAnswers as $answer)
                        <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ $answer->position }}</span>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $answer->question->text }}</p>
                                    @if ($answer->question->image)
                                        <img src="{{ route('files.show', $answer->question->image) }}" alt="Gambar soal" class="mt-3 max-h-64 rounded-md border border-gray-200 object-contain">
                                    @endif

                                    @if ($answer->question->isMultipleChoice())
                                        <div class="mt-4 space-y-2">
                                            @foreach (['a', 'b', 'c', 'd'] as $opt)
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
                        {{ $assessment->segments()->where('status', 'completed')->count() === $assessment->segments()->count() - 1 ? 'Selesai & Kirim' : 'Selesai Segment Ini →' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const segmentRemaining = {{ $currentSegment->remainingSeconds() }};
        const overallRemaining = {{ $assessment->ends_at ? $assessment->ends_at->diffInSeconds(now()) : 999999 }};
        const maxViolations = {{ config('assessment.max_security_blocks', 2) }};
        const assessmentId = {{ $assessment->id }};

        function startCountdowns() {
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

                if (segLeft <= 0) {
                    clearInterval(interval);
                    document.getElementById('assessment-form').submit();
                }

                if (overallLeft <= 0) {
                    clearInterval(interval);
                    window.location.href = '{{ route("assessment.result", $assessment) }}';
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
        }

        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());

        let violations = {{ $assessment->security_violations ?? 0 }};
        let armed = false;
        let locked = false;

        setTimeout(() => { armed = true; }, 1500);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden && armed && !locked && !document.getElementById('assessment-form').dataset.submitting) {
                locked = true;
                const payload = JSON.stringify({ reason: 'Tab beralih', answers: collectAnswers() });
                fetch('{{ route("assessment.security-violation", $assessment) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: payload,
                })
                .then(r => r.json())
                .then(data => {
                    if (data.submitted) { window.location.href = data.redirect; return; }
                    if (data.blocked) { window.location.reload(); return; }
                    violations++;
                    const remaining = maxViolations - violations + 1;
                    document.getElementById('violations-count').textContent = Math.max(0, remaining);
                    document.getElementById('security-message').textContent = 'Anda meninggalkan halaman assessment.';
                    document.getElementById('security-warning').classList.remove('hidden');
                    setTimeout(() => { locked = false; }, 3000);
                })
                .catch(() => { locked = false; });
            }
        });

        function collectAnswers() {
            const answers = {};
            document.querySelectorAll('#assessment-form [name^="answers"]').forEach(el => {
                const id = el.name.match(/\d+/)?.[0];
                if (id) answers[id] = el.value || '';
            });
            return answers;
        }

        startCountdowns();
    </script>
</x-app-layout>
