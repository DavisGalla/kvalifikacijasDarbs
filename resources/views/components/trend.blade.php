@props(['delta' => null, 'unit' => 'kg'])

@if ($delta !== null && abs($delta) > 0.0001)
    @php($up = $delta > 0)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 text-xs font-semibold tabular-nums ' . ($up ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400')]) }}
          title="{{ $up ? 'Up' : 'Down' }} {{ rtrim(rtrim(number_format(abs($delta), 2, '.', ''), '0'), '.') }} {{ $unit }} vs previous">
        {{ $up ? '▲' : '▼' }} {{ rtrim(rtrim(number_format(abs($delta), 2, '.', ''), '0'), '.') }}
    </span>
@elseif ($delta !== null)
    <span {{ $attributes->merge(['class' => 'text-xs font-semibold text-gray-400']) }} title="Same as previous">▬</span>
@endif
