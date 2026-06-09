<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('CMS User') }}</h2>
            <a href="{{ route('admin.users.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah User</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 grid gap-6 lg:grid-cols-2">
                <div class="bg-white p-4 sm:p-5 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Invite Peserta Test</h3>
                    <form method="POST" action="{{ route('admin.users.invite') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                        @csrf
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label for="invite_email" class="block sm:sr-only text-xs sm:text-inherit font-medium text-gray-700 mb-1 sm:mb-0">Email peserta</label>
                            <input id="invite_email" type="email" name="email" value="{{ old('email') }}" placeholder="email peserta" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <label for="invite_name" class="block sm:sr-only text-xs sm:text-inherit font-medium text-gray-700 mb-1 sm:mb-0">Nama peserta</label>
                            <input id="invite_name" type="text" name="name" value="{{ old('name') }}" placeholder="nama peserta" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="invite_question_package_id" class="block sm:sr-only text-xs sm:text-inherit font-medium text-gray-700 mb-1 sm:mb-0">Paket soal</label>
                            <select id="invite_question_package_id" name="question_package_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua paket</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" @selected(old('question_package_id') == $package->id)>{{ $package->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('question_package_id')" class="mt-2" />
                        </div>
                        <div>
                            <label for="access_days" class="block sm:sr-only text-xs sm:text-inherit font-medium text-gray-700 mb-1 sm:mb-0">Hari akses</label>
                            <input id="access_days" type="number" name="access_days" value="{{ old('access_days', config('assessment.default_access_days')) }}" min="1" max="365" placeholder="hari akses" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('access_days')" class="mt-2" />
                        </div>
                        <div>
                            <label for="duration_hours" class="block sm:sr-only text-xs sm:text-inherit font-medium text-gray-700 mb-1 sm:mb-0">Jam pengerjaan</label>
                            <input id="duration_hours" type="number" name="duration_hours" value="{{ old('duration_hours', config('assessment.default_duration_minutes') / 60) }}" min="0.25" max="24" step="0.25" placeholder="jam tes" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('duration_hours')" class="mt-2" />
                        </div>
                        <button class="w-full sm:w-auto rounded-md bg-emerald-600 px-4 py-3 sm:py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 min-h-[44px]">
                            Generate & Kirim
                        </button>
                    </form>
                </div>

                <div class="bg-white p-4 sm:p-5 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Bulk Invite via CSV</h3>
                    <form method="POST" action="{{ route('admin.users.invite-bulk') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">Upload file CSV</label>
                            <input id="csv_file" type="file" name="csv_file" accept=".csv,.txt" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                            <p class="mt-1 text-xs text-gray-500">Kolom: email, nama, paket (header wajib). Paket opsional, dikosongkan jika tidak ada.</p>
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                        </div>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Upload & Undang Semua
                        </button>
                    </form>
                </div>
            </div>

            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-[1fr_220px_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select name="package" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua paket</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(request('package') == $package->id)>{{ $package->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
            </form>

            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Paket</th>
                                <th class="px-6 py-3">Akses Sampai</th>
                                <th class="px-6 py-3">Durasi</th>
                                <th class="px-6 py-3">Assessment</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $user->is_admin ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $user->is_admin ? 'Admin' : 'Peserta' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->questionPackage?->name ?? 'Semua paket' }}</td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $user->assessment_access_expires_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ round(($user->assessment_duration_minutes ?? config('assessment.default_duration_minutes')) / 60, 2) }} jam</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->assessments_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus user ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="font-medium text-rose-600 hover:text-rose-800">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">Belum ada user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
