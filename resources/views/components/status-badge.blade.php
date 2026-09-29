@props(['color' => 'gray'])

@php
    $colors = [
        'green' => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        'amber' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        'indigo' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
        'red' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    ];

    $dots = [
        'green' => 'bg-green-500',
        'amber' => 'bg-amber-500',
        'gray' => 'bg-gray-400',
        'indigo' => 'bg-indigo-500',
        'red' => 'bg-red-500',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide whitespace-nowrap ' . ($colors[$color] ?? $colors['gray'])]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dots[$color] ?? $dots['gray'] }}"></span>
    {{ $slot }}
</span>
