<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 py-12 px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('competitions.show', $competition) }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to {{ $competition->title }}
            </a>

            <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-indigo-600">{{ $competition->sport->name }}</p>
                    <h1 class="mt-2 text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">
                        Results — {{ $competition->title }}
                    </h1>
                </div>

                <div class="px-8 py-8">
                    <div class="mb-8">
                        @if ($competition->winner)
                            <div class="flex items-center justify-between gap-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                    🏆 Winner: {{ $competition->winner->name }}
                                </p>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('competitions.winner.destroy', $competition) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-amber-800 dark:text-amber-300 hover:underline">
                                            Clear
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @elseif ($canManage)
                            @if ($winnerOptions->isEmpty())
                                <p class="text-sm text-gray-600 dark:text-gray-400">No confirmed participants yet to declare a winner.</p>
                            @else
                                <form method="POST" action="{{ route('competitions.winner.update', $competition) }}" class="flex items-center gap-3">
                                    @csrf
                                    @method('PUT')
                                    <select name="winner_id" required onchange="this.form.winner_type.value = this.options[this.selectedIndex].dataset.type"
                                            class="flex-1 rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        <option value="">Declare the tournament winner…</option>
                                        @foreach ($winnerOptions as $option)
                                            <option value="{{ $option['id'] }}" data-type="{{ $option['type'] }}">{{ $option['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="winner_type" value="{{ $winnerOptions->first()['type'] ?? '' }}">
                                    <button type="submit" class="rounded-xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700">
                                        Save winner
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>

                    @if ($competition->registration_mode === 'team')
                        @if ($matchups->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-400">No matchups have been posted yet.</p>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($matchups as $matchup)
                                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                        <span class="text-gray-800 dark:text-gray-200">
                                            {{ $matchup->homeTeam?->name ?? 'Unknown' }}
                                            <span class="mx-2 font-semibold text-gray-900 dark:text-gray-100">{{ $matchup->home_score }} – {{ $matchup->away_score }}</span>
                                            {{ $matchup->awayTeam?->name ?? 'Unknown' }}
                                        </span>
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('competitions.matchups.destroy', [$competition, $matchup]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">
                                                    Remove
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($canManage)
                            <div class="mt-10 border-t border-gray-100 dark:border-gray-700 pt-8">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add a matchup</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Only confirmed teams can be entered into a matchup.
                                </p>

                                @if ($confirmedTeams->count() < 2)
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">At least two confirmed teams are needed to record a matchup.</p>
                                @else
                                    <form method="POST" action="{{ route('competitions.matchups.store', $competition) }}"
                                          class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                        @csrf
                                        <select name="home_team_id" required class="rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                            <option value="">Home team</option>
                                            @foreach ($confirmedTeams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="home_score" min="0" required placeholder="0"
                                               class="w-20 rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        <span class="text-gray-400">–</span>
                                        <input type="number" name="away_score" min="0" required placeholder="0"
                                               class="w-20 rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        <select name="away_team_id" required class="rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                            <option value="">Away team</option>
                                            @foreach ($confirmedTeams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-xl bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                            Save
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @else
                        @if ($results->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-400">No results have been posted yet.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        <th class="pb-3">Position</th>
                                        <th class="pb-3">Participant</th>
                                        <th class="pb-3 text-right">{{ $competition->sport->result_type === 'time' ? 'Time' : 'Score' }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($results as $result)
                                        <tr>
                                            <td class="py-3 font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $result->position ?? '—' }}
                                            </td>
                                            <td class="py-3 text-gray-700 dark:text-gray-300">
                                                {{ $result->registrant?->name ?? 'Unknown' }}
                                            </td>
                                            <td class="py-3 text-right text-gray-700 dark:text-gray-300">
                                                {{ $result->formattedValue() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($canManage)
                            <div class="mt-10 border-t border-gray-100 dark:border-gray-700 pt-8">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Enter results</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Only confirmed participants can be scored. Positions are calculated automatically.
                                </p>

                                @if ($registrations->isEmpty())
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">No confirmed participants yet.</p>
                                @else
                                    <div class="mt-4 space-y-3">
                                        @foreach ($registrations as $registration)
                                            @php($existing = $results->get("{$registration->registrant_type}:{$registration->registrant_id}"))
                                            <form method="POST" action="{{ route('competitions.results.store', $competition) }}"
                                                  class="flex items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                                @csrf
                                                <input type="hidden" name="registrant_type" value="{{ $registration->registrant_type }}">
                                                <input type="hidden" name="registrant_id" value="{{ $registration->registrant_id }}">
                                                <span class="flex-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $registration->registrant?->name ?? 'Unknown' }}
                                                </span>
                                                <input type="number" step="0.001" name="value" required
                                                       value="{{ old('value', $existing?->value) }}"
                                                       placeholder="{{ $competition->sport->result_type === 'time' ? 'Seconds' : 'Score' }}"
                                                       class="w-32 rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                                <button type="submit" class="rounded-xl bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                                    Save
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif

                    @if ($competition->organizer_id === auth()->id())
                        <div class="mt-10 border-t border-gray-100 dark:border-gray-700 pt-8">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Officials</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Officials you assign can enter and edit results for this competition alongside you.
                            </p>

                            @if ($competition->officials->isNotEmpty())
                                <ul class="mt-4 space-y-2">
                                    @foreach ($competition->officials as $official)
                                        <li class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $official->name }}</span>
                                            <form method="POST" action="{{ route('competitions.officials.destroy', [$competition, $official]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">
                                                    Remove
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <form method="POST" action="{{ route('competitions.officials.store', $competition) }}" class="mt-4 flex items-center gap-3">
                                @csrf
                                <input type="text" name="identifier" required placeholder="Username or email"
                                       class="flex-1 rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <button type="submit" class="rounded-xl bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-700">
                                    Add official
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
