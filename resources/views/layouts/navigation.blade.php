@php
    $authUser = Auth::user();
    $visibleTypes = $authUser?->visiblePackageTypes() ?? [];
    $currentType = request('type');
    $typeLabel = fn (?string $type): string => \App\Models\QuestionPackage::typeLabel($type);
    $activeSubLink = 'font-semibold text-indigo-700 bg-indigo-50 border-r-2 border-indigo-400';
    $inactiveSubLink = 'text-gray-500 hover:text-gray-800 hover:bg-gray-50 border-r-2 border-transparent';
    $subLinkBase = 'flex items-center w-full pl-8 pr-3 py-2 text-sm rounded-lg transition duration-150 ease-in-out';
@endphp

{{-- Mobile Toggle Button --}}
<div class="fixed top-4 left-4 z-40 sm:hidden">
    <button @click="sidebarOpen = !sidebarOpen"
            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md p-3 text-gray-500 transition duration-150 ease-in-out hover:bg-white hover:text-gray-700 hover:shadow-sm focus:outline-none">
        <svg x-show="!sidebarOpen" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg x-show="sidebarOpen" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>

{{-- Desktop Re-open Button --}}
<button x-show="!sidebarOpen"
        @click="sidebarOpen = true"
        class="fixed left-0 top-1/2 z-30 hidden -translate-y-1/2 items-center gap-2 rounded-r-lg border border-l-0 border-gray-200 bg-white px-3 py-4 shadow-md transition-all hover:bg-gray-50 hover:shadow-lg sm:flex"
        style="display: none;" x-cloak>
    <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
    </svg>
    <span class="hidden text-xs font-medium text-gray-500 md:inline">Menu</span>
</button>

{{-- Sidebar --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition ease-in-out duration-300"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-gray-200 bg-white"
     style="display: none;" x-cloak>

    <div class="flex h-16 shrink-0 items-center border-b border-gray-100 px-5">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3">
            <span class="flex shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm" style="width: 36px; height: 36px;">
                <img src="{{ asset('favicon-48x48.png') }}" alt="Assessment" style="display: block; width: 26px; height: 26px; max-width: 26px; max-height: 26px; object-fit: contain;">
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-gray-950">
                    @if ($authUser?->isSuperAdmin())
                        Screening Assessment
                    @elseif (! empty($visibleTypes))
                        Screening {{ $typeLabel($visibleTypes[0]) }}
                    @else
                        Screening Assessment
                    @endif
                </span>
                <span class="block truncate text-xs text-gray-500">Admin Console</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="ml-2 hidden shrink-0 items-center text-gray-400 transition-colors hover:text-gray-700 sm:inline-flex">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>
    </div>

    <div class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @if ($authUser?->isSuperAdmin())
            @php
                $sectionLinks = [
                    [
                        'title' => 'Dashboard',
                        'active' => request()->routeIs('dashboard') && ! request()->routeIs('admin.*'),
                        'allLabel' => 'Dashboard (Semua)',
                        'allUrl' => route('dashboard'),
                        'typeRoute' => 'dashboard',
                    ],
                    [
                        'title' => 'Assessment',
                        'active' => request()->routeIs('admin.assessments.*'),
                        'allLabel' => 'Semua Assessment',
                        'allUrl' => route('admin.assessments.index'),
                        'typeRoute' => 'admin.assessments.index',
                    ],
                    [
                        'title' => 'Paket Soal',
                        'active' => request()->routeIs('admin.packages.*'),
                        'allLabel' => 'Semua Paket',
                        'allUrl' => route('admin.packages.index'),
                        'typeRoute' => 'admin.packages.index',
                    ],
                    [
                        'title' => 'User',
                        'active' => request()->routeIs('admin.users.*') && ! request()->routeIs('admin.invite'),
                        'allLabel' => 'Semua User',
                        'allUrl' => route('admin.users.index'),
                        'typeRoute' => 'admin.users.index',
                    ],
                ];
                $isReview = request()->routeIs('admin.she-review.*');
            @endphp

            @foreach ($sectionLinks as $section)
                <div x-data="{ open: {{ $section['active'] ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex w-full items-center rounded-lg border-r-2 border-transparent px-3 py-2.5 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900">
                        <span class="flex-1 text-left">{{ $section['title'] }}</span>
                        <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 space-y-0.5" style="display: none;">
                        <a href="{{ $section['allUrl'] }}" class="{{ $subLinkBase }} {{ $section['active'] && ! $currentType ? $activeSubLink : $inactiveSubLink }}">{{ $section['allLabel'] }}</a>
                        @foreach ($visibleTypes as $type)
                            <a href="{{ route($section['typeRoute'], ['type' => $type]) }}" class="{{ $subLinkBase }} {{ $section['active'] && $currentType === $type ? $activeSubLink : $inactiveSubLink }}">{{ $typeLabel($type) }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div x-data="{ open: {{ $isReview ? 'true' : 'false' }} }">
                <button @click="open = !open" class="flex w-full items-center rounded-lg border-r-2 border-transparent px-3 py-2.5 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900">
                    <span class="flex-1 text-left">Review</span>
                    <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <div x-show="open" class="mt-1 space-y-0.5" style="display: none;">
                    @if (in_array(\App\Models\QuestionPackage::TYPE_SHE, $visibleTypes, true))
                        <a href="{{ route('admin.she-review.index') }}" class="{{ $subLinkBase }} {{ $isReview ? $activeSubLink : $inactiveSubLink }}">SHE</a>
                    @endif
                </div>
            </div>

            <x-nav-link :href="route('admin.invite')" :active="request()->routeIs('admin.invite')">
                {{ __('Invite Peserta') }}
            </x-nav-link>

            <x-nav-link :href="route('admin.activity-logs.index')" :active="request()->routeIs('admin.activity-logs.*')">
                {{ __('Log Aktivitas') }}
            </x-nav-link>
        @elseif ($authUser?->isAdmin())
            @php
                $adminType = $visibleTypes[0] ?? null;
                $adminTypeLabel = $typeLabel($adminType);
                $menuLabels = [
                    'dashboard' => 'Dashboard '.$adminTypeLabel,
                    'assessment' => 'Assessment '.$adminTypeLabel,
                    'review' => 'Review '.$adminTypeLabel,
                    'packages' => 'Paket Soal '.$adminTypeLabel,
                    'users' => 'Peserta '.$adminTypeLabel,
                    'invite' => 'Invite Peserta '.$adminTypeLabel,
                    'logs' => 'Log Aktivitas',
                ];
            @endphp

            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __($menuLabels['dashboard']) }}
            </x-nav-link>
            <x-nav-link :href="route('admin.assessments.index')" :active="request()->routeIs('admin.assessments.*')">
                {{ __($menuLabels['assessment']) }}
            </x-nav-link>
            <x-nav-link :href="route('admin.packages.index')" :active="request()->routeIs('admin.packages.*')">
                {{ __($menuLabels['packages']) }}
            </x-nav-link>
            @if ($adminType === \App\Models\QuestionPackage::TYPE_SHE)
                <x-nav-link :href="route('admin.she-review.index')" :active="request()->routeIs('admin.she-review.*')">
                    {{ __('Review SHE') }}
                </x-nav-link>
            @endif
            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*') && !request()->routeIs('admin.invite')">
                {{ __($menuLabels['users']) }}
            </x-nav-link>
            <x-nav-link :href="route('admin.invite')" :active="request()->routeIs('admin.invite')">
                {{ __($menuLabels['invite']) }}
            </x-nav-link>
            <x-nav-link :href="route('admin.activity-logs.index')" :active="request()->routeIs('admin.activity-logs.*')">
                {{ __($menuLabels['logs']) }}
            </x-nav-link>
        @endif
    </div>

    <div class="shrink-0 border-t border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-700">
                {{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900">{{ $authUser?->name }}</p>
                <p class="truncate text-xs text-gray-500">{{ $authUser?->email }}</p>
                @php
                    $roleLabels = [
                        'super_admin' => 'Super Admin',
                        'admin_mekanik' => 'Admin Mekanik',
                        'admin_operation' => 'Admin Operator',
                        'admin_she' => 'Admin SHE',
                        'admin_hr' => 'Admin HR',
                        'user' => 'Peserta',
                    ];
                    $roleColors = [
                        'super_admin' => 'text-red-600',
                        'admin_mekanik' => 'text-indigo-600',
                        'admin_operation' => 'text-purple-600',
                        'admin_she' => 'text-cyan-600',
                        'admin_hr' => 'text-rose-600',
                        'user' => 'text-emerald-600',
                    ];
                @endphp
                <p class="truncate text-xs font-medium {{ $roleColors[$authUser?->role] ?? 'text-gray-500' }}">{{ $roleLabels[$authUser?->role] ?? $authUser?->role }}</p>
            </div>

            <div class="relative" x-data="{ userMenu: false }" @click.outside="userMenu = false">
                <button @click="userMenu = !userMenu"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-600 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>

                <div x-show="userMenu"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute bottom-full right-0 mb-2 w-48 origin-bottom-right overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5"
                     style="display: none;"
                     @click="userMenu = false">
                    <a href="{{ route('profile.edit') }}" class="flex min-h-[44px] items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-100">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex min-h-[44px] w-full items-center px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-100">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
