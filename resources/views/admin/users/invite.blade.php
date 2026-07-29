<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Invite Peserta') }}</h2>
            <a href="{{ route('admin.users.index') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">Kembali ke User</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">

                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Invite Peserta Test</h3>
                    <p class="mt-1 text-sm text-gray-600">Buat akun peserta baru dan kirim email undangan.</p>
                    <form method="POST" action="{{ route('admin.users.invite') }}" class="mt-4 space-y-4">
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
                                @foreach (($visibleTypes ?? \App\Models\QuestionPackage::TYPES) as $type)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ \App\Models\QuestionPackage::typeLabel($type) }}</option>
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

                        <button class="w-full rounded-md bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 min-h-[44px]">
                            Generate & Kirim Undangan
                        </button>
                    </form>
                </div>

                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Bulk Invite via CSV</h3>
                    <p class="mt-1 text-sm text-gray-600">Upload file CSV untuk mengundang banyak peserta sekaligus.</p>
                    <form method="POST" action="{{ route('admin.users.invite-bulk') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700">Upload file CSV</label>
                            <input id="csv_file" type="file" name="csv_file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                            <p class="mt-1 text-xs text-gray-500">Kolom: email, nama, paket, tipe (operator/mekanik/she/hr). Tipe & paket opsional.</p>
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-1" />
                        </div>
                        <button class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 min-h-[44px]">
                            Upload & Undang Semua
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('invite_type');
            const packageSelect = document.getElementById('invite_question_package_id');

            typeSelect.addEventListener('change', filterPackages);

            filterPackages();

            function filterPackages() {
                const selectedType = typeSelect.value;
                const options = packageSelect.querySelectorAll('option[data-type]');
                packageSelect.querySelector('option[value=""]').selected = true;
                options.forEach(function (option) {
                    option.style.display = (!selectedType || option.dataset.type === selectedType) ? '' : 'none';
                });
            }
        });
    </script>
</x-app-layout>
