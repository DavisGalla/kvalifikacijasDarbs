@props(['results', 'competition', 'winner' => null])

{{-- Ranked results table. Works for any sport; only the value column
     formatting differs, driven by sports.result_type in Result::formattedValue(). --}}
@php
    $isWinner = fn ($result) => $winner
        && $winner->getMorphClass() === $result->registrant_type
        && $winner->getKey() === (int) $result->registrant_id;
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900/40">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <th class="px-4 py-3 w-16">Rank</th>
                <th class="px-4 py-3">{{ $competition->registration_mode === 'team' ? 'Team' : 'Participant' }}</th>
                <th class="px-4 py-3 text-right">{{ $competition->sport->result_type === 'time' ? 'Time' : 'Score' }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($results as $result)
                @php($top = $result->position === 1 || $isWinner($result))
                <tr class="{{ $top ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                        @if ($top)
                            <span title="Winner" aria-label="Winner">🏆</span>
                        @else
                            {{ $result->position ?? '—' }}
                        @endif
                    </td>
                    <td class="px-4 py-3 {{ $top ? 'font-semibold text-gray-900 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $result->registrant?->name ?? 'Unknown' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums font-medium text-gray-900 dark:text-gray-100">
                        {{ $result->formattedValue() }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
