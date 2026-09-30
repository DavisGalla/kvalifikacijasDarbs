@props(['label' => false])

{{-- Light/dark switch. Persists to localStorage; see layouts/theme-script. --}}
<button type="button"
        x-data="{ dark: document.documentElement.classList.contains('dark') }"
        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
        :aria-label="dark ? 'Switch to light theme' : 'Switch to dark theme'"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 p-2 rounded-lg text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition']) }}>
    {{-- sun (shown in dark mode) --}}
    <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42M3 12h2m14 0h2M5.64 18.36l1.42-1.42m9.88-9.88 1.42-1.42M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />
    </svg>
    {{-- moon (shown in light mode) --}}
    <svg x-show="!dark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
    </svg>
    @if ($label)
        <span x-text="dark ? 'Light theme' : 'Dark theme'"></span>
    @endif
</button>
