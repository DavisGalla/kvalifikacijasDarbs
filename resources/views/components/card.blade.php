@props(['href' => null, 'padding' => 'p-6'])

{{-- The one card style used across competitions, teams, blog and dashboard. --}}
@php
    $base = "bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm $padding";
    $interactive = 'group block hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $interactive"]) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</div>
@endif
