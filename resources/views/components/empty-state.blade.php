@props(['icon' => '🏆', 'message', 'cta' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'text-center py-16']) }}>
    <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4 text-2xl">{{ $icon }}</div>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">{{ $message }}</p>
    @if ($cta && $href)
        <a href="{{ $href }}"
           class="mt-5 inline-flex items-center gap-2 bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150">
            {{ $cta }}
        </a>
    @endif
</div>
