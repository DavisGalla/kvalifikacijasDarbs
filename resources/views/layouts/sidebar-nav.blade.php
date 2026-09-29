{{-- Logo --}}
<div class="h-16 flex items-center gap-2 px-5 border-b border-gray-100 dark:border-gray-800 shrink-0">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-lg text-gray-900 dark:text-gray-100">
        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
        SportWeb
    </a>
</div>

{{-- Links --}}
<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10" />
        </svg>
        {{ __('Dashboard') }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('calendar.index')" :active="request()->routeIs('calendar.index')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
        </svg>
        {{ __('Calendar') }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('pbs.index')" :active="request()->routeIs('pbs.index')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" />
        </svg>
        {{ __("PB's") }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('blog.index')" :active="request()->routeIs('blog.index')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" />
        </svg>
        {{ __('Blog') }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('competitions.index')" :active="request()->routeIs('competitions.index') || request()->routeIs('competitions.show') || request()->routeIs('competitions.create') || request()->routeIs('competitions.results.index')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 1h13l-2 3.5L17 12H4" />
        </svg>
        {{ __('Competitions') }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('teams.index')" :active="request()->routeIs('teams.*')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m5-2.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-2a4 4 0 1 0 0-8m-14 8a4 4 0 1 1 0-8" />
        </svg>
        {{ __('Teams') }}
    </x-sidebar-link>

    <x-sidebar-link :href="route('competitions.history')" :active="request()->routeIs('competitions.history')" @click="mobileNavOpen = false">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        {{ __('My history') }}
    </x-sidebar-link>
</nav>

{{-- Account --}}
<div class="border-t border-gray-100 dark:border-gray-800 p-3 shrink-0">
    <x-dropdown align="left" width="56" direction="up">
        <x-slot name="trigger">
            <button class="w-full flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition text-left">
                <span class="w-8 h-8 rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 flex items-center justify-center text-xs font-semibold shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ Auth::user()->name }}</span>
                    <span class="block text-xs text-gray-400 dark:text-gray-500 truncate">{{ Auth::user()->email }}</span>
                </span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6-4 4-4-4" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')" @click="mobileNavOpen = false">
                {{ __('Profile') }}
            </x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</div>
