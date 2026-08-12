<x-app-layout>
    @php
        $inviteTypes = $visibleTypes ?? \App\Models\QuestionPackage::TYPES;
        $defaultInviteType = count($inviteTypes) === 1 ? $inviteTypes[0] : null;
        $selectedBulkType = old('bulk_type', $defaultInviteType);
        $selectedSingleType = old('type', $defaultInviteType);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">{{ __('Invite Peserta') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Generate akun, password random, dan kirim link assessment ke email peserta.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">Kembali ke User</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Kirim Banyak Email</h3>
                        <p class="mt-1 text-sm text-gray-600">Paste banyak email sekaligus. Sistem akan membuat akun dan password berbeda untuk setiap peserta.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Rekomendasi</span>
                </div>

                <form method="POST" action="{{ route('admin.users.invite-many') }}" class="mt-5 space-y-5" data-confirm
                      data-confirm-title="Generate dan kirim ke banyak email?"
                      data-confirm-message="Sistem akan membuat akun, password random, dan mengirim email ke semua alamat valid."
                      data-confirm-text="Ya, kirim semua">
                    @csrf

                    <div>
                        <label for="bulk_emails" class="block text-sm font-medium text-gray-700">Daftar email peserta</label>
                        <textarea
                            id="bulk_emails"
                            name="bulk_emails"
                            rows="8"
                            placeholder="andi@example.com&#10;Budi Santoso <budi@example.com>&#10;cici@example.com, dodi@example.com"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >{{ old('bulk_emails') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Bisa satu email per baris, dipisah koma, atau format Nama &lt;email@domain.com&gt;. Maksimal 200 email sekali kirim.</p>
                        <x-input-error :messages="$errors->get('bulk_emails')" class="mt-1" />
                    </div>

                    <div class="grid gap-4 lg:grid-cols-5">
                        <div>
                            <label for="bulk_invite_type" class="block text-sm font-medium text-gray-700">Tipe Peserta</label>
                            <select id="bulk_invite_type" name="bulk_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Pilih tipe</option>
                                @foreach ($inviteTypes as $type)
                                    <option value="{{ $type }}" @selected($selectedBulkType === $type)>{{ \App\Models\QuestionPackage::typeLabel($type) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bulk_type')" class="mt-1" />
                        </div>
                        <div>
                            <label for="bulk_invite_question_package_id" class="block text-sm font-medium text-gray-700">Paket soal</label>
                            <select id="bulk_invite_question_package_id" name="bulk_question_package_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua paket</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" data-type="{{ $package->type }}" @selected(old('bulk_question_package_id') == $package->id)>{{ $package->name }} ({{ \App\Models\QuestionPackage::typeLabel($package->type) }}{{ $package->level ? ' - '.$package->level : '' }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bulk_question_package_id')" class="mt-1" />
                        </div>
                        <div id="bulk_operator_category_wrap">
                            <label for="bulk_operator_assessment_category_id" class="block text-sm font-medium text-gray-700">Kategori Invite</label>
                            <select id="bulk_operator_assessment_category_id" name="bulk_operator_assessment_category_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tanpa kategori</option>
                                @foreach ($operatorCategories as $category)
                                    <option value="{{ $category->id }}" @selected(old('bulk_operator_assessment_category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bulk_operator_assessment_category_id')" class="mt-1" />
                        </div>
                        <div>
                            <label for="bulk_access_days" class="block text-sm font-medium text-gray-700">Hari akses</label>
                            <input id="bulk_access_days" type="number" name="bulk_access_days" value="{{ old('bulk_access_days', config('assessment.default_access_days')) }}" min="1" max="365" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('bulk_access_days')" class="mt-1" />
                        </div>
                        <div>
                            <label for="bulk_duration_hours" class="block text-sm font-medium text-gray-700">Jam pengerjaan</label>
                            <input id="bulk_duration_hours" type="number" name="bulk_duration_hours" value="{{ old('bulk_duration_hours', config('assessment.default_duration_minutes') / 60) }}" min="0.25" max="24" step="0.25" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('bulk_duration_hours')" class="mt-1" />
                        </div>
                    </div>

                    <button class="inline-flex min-h-[44px] w-full items-center justify-center rounded-md bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 sm:w-auto">
                        Generate & Kirim Semua
                    </button>
                </form>
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-950">Invite Satu Peserta</h3>
                    <p class="mt-1 text-sm text-gray-600">Tetap tersedia kalau hanya perlu kirim ke satu kandidat.</p>
                    <form method="POST" action="{{ route('admin.users.invite') }}" class="mt-4 space-y-4" data-confirm
                          data-confirm-title="Generate dan kirim undangan?"
                          data-confirm-message="Sistem akan membuat akun peserta, password random, dan mengirim link assessment."
                          data-confirm-text="Ya, kirim undangan">
                        @csrf
                        <div>
                            <label for="invite_name" class="block text-sm font-medium text-gray-700">Nama peserta</label>
                            <input id="invite_name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama peserta" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-gray-700">Email peserta</label>
                            <input id="invite_email" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <div>
                            <label for="invite_type" class="block text-sm font-medium text-gray-700">Tipe Peserta</label>
                            <select id="invite_type" name="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Pilih tipe</option>
                                @foreach ($inviteTypes as $type)
                                    <option value="{{ $type }}" @selected($selectedSingleType === $type)>{{ \App\Models\QuestionPackage::typeLabel($type) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-1" />
                        </div>
                        <div>
                            <label for="invite_question_package_id" class="block text-sm font-medium text-gray-700">Paket soal</label>
                            <select id="invite_question_package_id" name="question_package_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua paket</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" data-type="{{ $package->type }}" @selected(old('question_package_id') == $package->id)>{{ $package->name }} ({{ \App\Models\QuestionPackage::typeLabel($package->type) }}{{ $package->level ? ' - '.$package->level : '' }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('question_package_id')" class="mt-1" />
                        </div>
                        <div id="operator_category_wrap">
                            <label for="operator_assessment_category_id" class="block text-sm font-medium text-gray-700">Kategori Invite</label>
                            <select id="operator_assessment_category_id" name="operator_assessment_category_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tanpa kategori</option>
                                @foreach ($operatorCategories as $category)
                                    <option value="{{ $category->id }}" @selected(old('operator_assessment_category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('operator_assessment_category_id')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="access_days" class="block text-sm font-medium text-gray-700">Hari akses</label>
                                <input id="access_days" type="number" name="access_days" value="{{ old('access_days', config('assessment.default_access_days')) }}" min="1" max="365" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <x-input-error :messages="$errors->get('access_days')" class="mt-1" />
                            </div>
                            <div>
                                <label for="duration_hours" class="block text-sm font-medium text-gray-700">Jam pengerjaan</label>
                                <input id="duration_hours" type="number" name="duration_hours" value="{{ old('duration_hours', config('assessment.default_duration_minutes') / 60) }}" min="0.25" max="24" step="0.25" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <x-input-error :messages="$errors->get('duration_hours')" class="mt-1" />
                            </div>
                        </div>

                        <button class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 min-h-[44px]">
                            Generate & Kirim Undangan
                        </button>
                    </form>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-950">Bulk Invite via CSV</h3>
                    <p class="mt-1 text-sm text-gray-600">Untuk data yang sudah rapi dalam file CSV.</p>
                    <form method="POST" action="{{ route('admin.users.invite-bulk') }}" enctype="multipart/form-data" class="mt-4 space-y-4" data-confirm
                          data-confirm-title="Upload CSV dan undang peserta?"
                          data-confirm-message="Data pada CSV akan diproses menjadi akun peserta dan dikirimkan email undangan."
                          data-confirm-text="Ya, upload dan kirim">
                        @csrf
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700">Upload file CSV</label>
                            <input id="csv_file" type="file" name="csv_file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                            <p class="mt-1 text-xs text-gray-500">Kolom: email, nama, paket, tipe (operator/mekanik/she/hr), kategori. Tipe, paket, dan kategori opsional.</p>
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-1" />
                        </div>
                        <button class="w-full rounded-md bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-black min-h-[44px]">
                            Upload & Undang Semua
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            filterPackageSelect('bulk_invite_type', 'bulk_invite_question_package_id');
            filterPackageSelect('invite_type', 'invite_question_package_id');
            syncOperatorCategory('bulk_invite_type', 'bulk_operator_category_wrap', 'bulk_operator_assessment_category_id');
            syncOperatorCategory('invite_type', 'operator_category_wrap', 'operator_assessment_category_id');

            function filterPackageSelect(typeId, packageId) {
                const typeSelect = document.getElementById(typeId);
                const packageSelect = document.getElementById(packageId);

                if (!typeSelect || !packageSelect) return;

                typeSelect.addEventListener('change', function () {
                    syncPackages(true);
                });

                syncPackages(false);

                function syncPackages(resetSelected) {
                    const selectedType = typeSelect.value;
                    const currentValue = packageSelect.value;
                    let currentStillVisible = !currentValue;

                    packageSelect.querySelectorAll('option[data-type]').forEach(function (option) {
                        const visible = !selectedType || option.dataset.type === selectedType;
                        option.hidden = !visible;
                        option.disabled = !visible;

                        if (visible && option.value === currentValue) {
                            currentStillVisible = true;
                        }
                    });

                    if (resetSelected || !currentStillVisible) {
                        packageSelect.value = '';
                    }
                }
            }

            function syncOperatorCategory(typeId, wrapId, selectId) {
                const typeSelect = document.getElementById(typeId);
                const wrap = document.getElementById(wrapId);
                const select = document.getElementById(selectId);

                if (!typeSelect || !wrap || !select) return;

                typeSelect.addEventListener('change', sync);
                sync();

                function sync() {
                    const usesCategory = ['mekanik', 'operator'].includes(typeSelect.value);
                    wrap.classList.toggle('hidden', !usesCategory);
                    select.disabled = !usesCategory;
                    if (!usesCategory) {
                        select.value = '';
                    }
                }
            }
        });
    </script>
</x-app-layout>
