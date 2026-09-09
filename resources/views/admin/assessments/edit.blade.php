<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Edit Assessment</h2>
                <p class="mt-1 text-sm text-gray-500">Ubah data assessment peserta dan status pengerjaannya.</p>
            </div>
            <a href="{{ route('admin.assessments.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.assessments.update', $assessment) }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6" data-confirm
                  data-confirm-title="Simpan perubahan assessment?"
                  data-confirm-message="Data assessment peserta akan diperbarui sesuai input."
                  data-confirm-text="Ya, simpan">
                @csrf
                @method('PUT')

                <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    Peserta: <span class="font-semibold">{{ $assessment->user->name }}</span>
                    <span class="text-sky-700">({{ $assessment->user->email }})</span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="question_package_id" value="Paket Soal" />
                        <select id="question_package_id" name="question_package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Tanpa Paket --</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}" @selected(old('question_package_id', $assessment->question_package_id) == $package->id)>{{ $package->name }} ({{ \App\Models\QuestionPackage::typeLabel($package->type) }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="operator_assessment_category_id" value="Kategori Invite" />
                        <select id="operator_assessment_category_id" name="operator_assessment_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach ($operatorCategories as $category)
                                <option value="{{ $category->id }}" @selected(old('operator_assessment_category_id', $assessment->operator_assessment_category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('operator_assessment_category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="site" value="Site" />
                        <select id="site" name="site" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @if(request()->user()?->hasSiteRestriction()) disabled @endif>
                            <option value="">-- Tanpa Site --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->code }}" @selected(old('site', $assessment->site ?: $assessment->user->site) === $site->code)>{{ $site->code }} — {{ $site->name }}</option>
                            @endforeach
                        </select>
                        @if(request()->user()?->hasSiteRestriction())
                            <input type="hidden" name="site" value="{{ request()->user()?->normalizedSite() }}">
                        @endif
                        <x-input-error :messages="$errors->get('site')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_mode" value="Status Assessment" />
                        <select id="status_mode" name="status_mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="running" @selected(old('status_mode', $assessment->isSubmitted() ? 'submitted' : ($assessment->isBlocked() ? 'blocked' : 'running')) === 'running')>Sedang Jalan</option>
                            <option value="blocked" @selected(old('status_mode', $assessment->isSubmitted() ? 'submitted' : ($assessment->isBlocked() ? 'blocked' : 'running')) === 'blocked')>Terblokir</option>
                            <option value="submitted" @selected(old('status_mode', $assessment->isSubmitted() ? 'submitted' : ($assessment->isBlocked() ? 'blocked' : 'running')) === 'submitted')>Sudah Test</option>
                            <option value="not_started" @selected(old('status_mode') === 'not_started')>Belum Mengerjakan</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Pilih Belum Mengerjakan untuk reset attempt ini.</p>
                        <x-input-error :messages="$errors->get('status_mode')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="total_questions" value="Total Soal" />
                        <input id="total_questions" type="number" name="total_questions" min="0" max="10000" value="{{ old('total_questions', $assessment->total_questions) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <x-input-error :messages="$errors->get('total_questions')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="correct_answers" value="Jawaban Benar" />
                        <input id="correct_answers" type="number" name="correct_answers" min="0" max="10000" value="{{ old('correct_answers', $assessment->correct_answers) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <x-input-error :messages="$errors->get('correct_answers')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="score" value="Nilai" />
                        <input id="score" type="number" name="score" min="0" max="100" step="0.01" value="{{ old('score', $assessment->score) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <x-input-error :messages="$errors->get('score')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="started_at" value="Mulai" />
                        <input id="started_at" type="datetime-local" name="started_at" value="{{ old('started_at', $assessment->started_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('started_at')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="submitted_at" value="Selesai / Submit" />
                        <input id="submitted_at" type="datetime-local" name="submitted_at" value="{{ old('submitted_at', $assessment->submitted_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('submitted_at')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                    <a href="{{ route('admin.assessments.index') }}" class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
                    <button class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Simpan Assessment</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
