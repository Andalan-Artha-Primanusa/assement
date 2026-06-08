<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Activity Log') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-6 flex gap-3">
                <select name="action" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
                    <a href="{{ route('admin.activity-logs.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-4 sm:px-6 py-3">Waktu</th>
                                <th class="px-4 sm:px-6 py-3">Admin</th>
                                <th class="px-4 sm:px-6 py-3">Aksi</th>
                                <th class="px-4 sm:px-6 py-3">Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $log->user->name }}</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">{{ $log->action }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 sm:px-6 py-10 text-center text-gray-500">Belum ada aktivitas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 sm:px-6 py-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
