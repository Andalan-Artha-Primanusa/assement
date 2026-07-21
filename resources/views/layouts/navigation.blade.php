{{-- Mobile Toggle Button --}}
<div class="fixed top-4 left-4 z-40 sm:hidden">
    <button @click="sidebarOpen = !sidebarOpen"
            class="inline-flex items-center justify-center p-3 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out min-h-[44px] min-w-[44px]">
        <svg x-show="!sidebarOpen" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg x-show="sidebarOpen" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>

{{-- Desktop Re-open Button (when sidebar is collapsed) --}}
<button x-show="!sidebarOpen"
        @click="sidebarOpen = true"
        class="fixed top-1/2 left-0 z-30 -translate-y-1/2 bg-white border border-gray-200 border-l-0 rounded-r-lg px-3 py-4 shadow-md hover:shadow-lg hover:bg-gray-50 transition-all hidden sm:flex items-center gap-2 group"
        style="display: none;" x-cloak>
    <svg class="w-5 h-5 text-indigo-500 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
    </svg>
    <span class="text-xs font-medium text-gray-500 group-hover:text-gray-700 hidden md:inline transition-colors">Menu</span>
</button>

{{-- Sidebar --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition ease-in-out duration-300"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 flex flex-col"
     style="display: none;" x-cloak>

    {{-- Logo + Desktop Collapse --}}
    <div class="shrink-0 flex items-center h-16 px-6 border-b border-gray-100">
        <a href="{{ route('dashboard') }}" class="flex-1 text-lg font-semibold text-gray-900 min-w-0 truncate">
            Screening Mechanic
        </a>
        <button @click="sidebarOpen = false" class="ml-2 shrink-0 flex items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors hidden sm:inline-flex group">
            <span class="text-xs font-medium text-gray-400 group-hover:text-gray-600 hidden lg:inline transition-colors">Tutup</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>
    </div>

    {{-- Navigation Links --}}
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>
        @if (Auth::user()->isAdmin())
            <x-nav-link :href="route('admin.assessments.index')" :active="request()->routeIs('admin.assessments.*')">
                {{ __('Assessment') }}
            </x-nav-link>
            <x-nav-link :href="route('admin.packages.index')" :active="request()->routeIs('admin.packages.*')">
                {{ __('Paket Soal') }}
            </x-nav-link>

            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*') && !request()->routeIs('admin.invite')">
                {{ __('User') }}
            </x-nav-link>
            <x-nav-link :href="route('admin.invite')" :active="request()->routeIs('admin.invite')">
                {{ __('Invite') }}
            </x-nav-link>
            @if (Auth::user()->isSuperAdmin())
                <x-nav-link :href="route('admin.activity-logs.index')" :active="request()->routeIs('admin.activity-logs.*')">
                    {{ __('Log Aktivitas') }}
                </x-nav-link>
            @endif
        @endif
    </div>

    {{-- User Menu at Bottom --}}
    <div class="shrink-0 p-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                @php
                    $roleLabels = [
                        'super_admin' => 'Super Admin',
                        'admin_mekanik' => 'Admin Mekanik',
                        'admin_operation' => 'Admin Operator',
                        'user' => 'Peserta',
                    ];
                    $roleColors = [
                        'super_admin' => 'text-red-600',
                        'admin_mekanik' => 'text-indigo-600',
                        'admin_operation' => 'text-purple-600',
                        'user' => 'text-emerald-600',
                    ];
                @endphp
                <p class="text-xs {{ $roleColors[Auth::user()->role] ?? 'text-gray-500' }} truncate font-medium">{{ $roleLabels[Auth::user()->role] ?? Auth::user()->role }}</p>
            </div>

            {{-- Three-dot Menu --}}
            <div class="relative" x-data="{ userMenu: false }" @click.outside="userMenu = false">
                <button @click="userMenu = !userMenu"
                        class="flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                     class="absolute right-0 bottom-full mb-2 w-48 origin-bottom-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;"
                     @click="userMenu = false">
                    <a href="{{ route('profile.edit') }}"
                       class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 min-h-[44px] flex items-center">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 min-h-[44px] flex items-center">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
