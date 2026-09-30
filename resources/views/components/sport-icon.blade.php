@props(['sport' => null])

@php
    $slug = strtolower($sport?->slug ?? $sport?->name ?? '');
    $icons = [
        'football' => '⚽', 'soccer' => '⚽', 'basketball' => '🏀', 'volleyball' => '🏐',
        'tennis' => '🎾', 'running' => '🏃', 'athletics' => '🏃', 'swimming' => '🏊',
        'cycling' => '🚴', 'hockey' => '🏒', 'baseball' => '⚾', 'rugby' => '🏉',
        'boxing' => '🥊', 'golf' => '⛳', 'weightlifting' => '🏋️', 'badminton' => '🏸',
    ];
    $icon = '🏅';
    foreach ($icons as $key => $value) {
        if (str_contains($slug, $key)) { $icon = $value; break; }
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-xl shrink-0']) }} aria-hidden="true">{{ $icon }}</span>
