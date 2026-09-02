<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('CMS User') }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.invite') }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Invite Peserta</a>
                <a href="{{ route('admin.users.create', ['type' => 'peserta']) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">+ Tambah Peserta</a>
                <a href="{{ route('admin.users.create', ['type' => 'admin']) }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-900">+ Tambah Admin</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg lg:grid-cols-[minmax(220px,1fr)_190px_190px_170px_170px_auto_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select name="package" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua paket</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(request('package') == $package->id)>{{ $package->name }}</option>
                    @endforeach
                </select>
                <select name="operator_category" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua kategori invite</option>
                    @foreach ($operatorCategories as $category)
                        <option value="{{ $category->id }}" @selected(request('operator_category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="site" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua site</option>
                    @foreach (($sites ?? collect()) as $site)
                        <option value="{{ $site }}" @selected(request('site') === $site)>{{ $site }}</option>
                    @endforeach
                </select>
                <select name="test_status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status test</option>
                    <option value="not_started" @selected(request('test_status') === 'not_started')>Belum Mengerjakan</option>
                    <option value="submitted" @selected(request('test_status') === 'submitted')>Sudah Test</option>
                    <option value="running" @selected(request('test_status') === 'running')>Sedang Jalan</option>
                    <option value="blocked" @selected(request('test_status') === 'blocked')>Terblokir</option>
                </select>
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
                <a href="{{ route('admin.users.index', array_filter(['type' => request('type')])) }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
            </form>

            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3">Paket</th>
                                <th class="px-6 py-3">Kategori Invite</th>
                                <th class="px-6 py-3">Site</th>
                                <th class="px-6 py-3">Segment</th>
                                <th class="px-6 py-3">Akses Sampai</th>
                                <th class="px-6 py-3 text-center">Durasi</th>
                                <th class="px-6 py-3 text-center">Status Test</th>
                                <th class="px-6 py-3 text-center">Assessment</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $roleColors = [
                                                'super_admin' => 'bg-red-50 text-red-700',
                                                'admin_mekanik' => 'bg-indigo-50 text-indigo-700',
                                                'admin_operation' => 'bg-purple-50 text-purple-700',
                                                'admin_she' => 'bg-cyan-50 text-cyan-700',
                                                'admin_hr' => 'bg-rose-50 text-rose-700',
                                                'user' => 'bg-emerald-50 text-emerald-700',
                                            ];
                                            $roleLabels = [
                                                'super_admin' => 'Super Admin',
                                                'admin_mekanik' => 'Admin Mekanik',
                                                'admin_operation' => 'Admin Operator',
                                                'admin_she' => 'Admin SHE',
                                                'admin_hr' => 'Admin HR',
                                                'user' => 'Peserta',
                                            ];
                                        @endphp
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $roleColors[$user->role] ?? 'bg-gray-50 text-gray-700' }}">
                                            {{ $roleLabels[$user->role] ?? $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($user->questionPackage)
                                            <div class="max-w-[260px]">
                                                <p class="truncate font-medium text-gray-900">{{ $user->questionPackage->name }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">{{ \App\Models\QuestionPackage::typeLabel($user->questionPackage->type) }}{{ $user->questionPackage->level ? ' - '.$user->questionPackage->level : '' }}</p>
                                            </div>
                                        @else
                                            <span class="text-gray-500">Semua paket</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($user->operatorAssessmentCategory)
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $user->operatorAssessmentCategory->name }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->site ?: '-' }}</td>
                                    <td class="px-6 py-4 text-xs">
                                        @if (!empty($user->segment_config) && is_array($user->segment_config))
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($user->segment_config as $seg)
                                                    @php
                                                        $segTypeLabels = ['multiple_choice' => 'PG', 'essay' => 'Essay', 'upload' => 'Upload'];
                                                    @endphp
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700 font-semibold">{{ $segTypeLabels[$seg['type']] ?? $seg['type'] }} {{ $seg['duration'] }}m</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->assessment_access_expires_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-gray-700">{{ round(($user->assessment_duration_minutes ?? config('assessment.default_duration_minutes')) / 60, 2) }} jam</td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($user->role !== \App\Models\User::ROLE_USER)
                                            <span class="text-gray-400">-</span>
                                        @elseif (($user->current_submitted_assessments_count ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sudah Test</span>
                                        @elseif (($user->current_blocked_assessments_count ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Terblokir</span>
                                        @elseif (($user->current_running_assessments_count ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Sedang Jalan</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Belum Mengerjakan</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-700">{{ $user->assessments_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($user->assessments_count > 0)
                                                <a href="{{ route('admin.users.answers', $user) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Lihat Jawaban</a>
                                            @endif
                                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm
                                                  data-confirm-title="Hapus user ini?"
                                                  data-confirm-message="User {{ $user->name }} akan dihapus dari sistem."
                                                  data-confirm-text="Ya, hapus user"
                                                  data-confirm-variant="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-6 py-10 text-center text-gray-500">Belum ada user.</td>
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
