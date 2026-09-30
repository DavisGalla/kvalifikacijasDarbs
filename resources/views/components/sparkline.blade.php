@props(['values', 'width' => 96, 'height' => 28])

{{-- Tiny inline-SVG line chart of a series of numbers. --}}
@php
    $values = collect($values)->map(fn ($v) => (float) $v)->values();
    $count = $values->count();
    $min = $values->min();
    $range = max($values->max() - $min, 0.0001);
    $pad = 3;
    $points = $values->map(function ($v, $i) use ($count, $min, $range, $width, $height, $pad) {
        $x = $count > 1 ? $pad + $i * (($width - 2 * $pad) / ($count - 1)) : $width / 2;
        $y = $height - $pad - (($v - $min) / $range) * ($height - 2 * $pad);

        return [round($x, 1), round($y, 1)];
    });
    $last = $points->last();
    $rising = $count > 1 && $values->last() >= $values->first();
@endphp

@if ($count >= 2)
    <svg {{ $attributes->merge(['class' => $rising ? 'text-green-500' : 'text-red-500']) }}
         width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" fill="none" role="img" aria-label="Progress over time">
        <polyline points="{{ $points->map(fn ($p) => "$p[0],$p[1]")->implode(' ') }}"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="2.5" fill="currentColor" />
    </svg>
@endif
