<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 py-12 px-4">
        <div class="max-w-3xl mx-auto">
            <x-back-link :href="route('competitions.show', $competition)">Back to {{ $competition->title }}</x-back-link>

            <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $competition->sport->name }}</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        Results — {{ $competition->title }}
                    </h1>
                </div>

                <div class="px-8 py-8">
                    <div class="mb-8 space-y-4">
                        @if ($competition->winner)
                            <div class="flex items-center justify-between gap-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                        🏆 Winner: {{ $competition->winner->name }}
                                    </p>
                                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                        @if ($competition->winner_method === 'automatic')
                                            Decided by the {{ $standings->isNotEmpty() ? 'standings' : 'results' }} and updated automatically when they change.
                                        @elseif ($competition->winner_method === 'manual')
                                            Chosen by the organizer: {{ $competition->winner_note }}
                                        @endif
                                    </p>
                                </div>
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
                        @elseif ($competition->winner_method === 'automatic')
                            <p class="rounded-xl border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                                The winner follows the results, but first place is currently tied or empty.
                            </p>
                        @endif

                        @if ($canManage && ! $competition->winner)
                            @if (! $competition->canDecideWinner())
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    The winner can be declared once the competition has finished ({{ $competition->end_time->format('M j, Y H:i') }}).
                                </p>
                            @elseif ($winnerOptions->isEmpty())
                                <p class="text-sm text-gray-600 dark:text-gray-400">No confirmed participants yet to declare a winner.</p>
                            @else
                                <form method="POST" action="{{ route('competitions.winner.update', $competition) }}" class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="method" value="automatic">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        @if ($leaderName)
                                            Current leader: <span class="font-semibold">{{ $leaderName }}</span>
                                        @else
                                            The results do not decide a single winner yet.
                                        @endif
                                    </p>
                                    <button type="submit" @disabled(! $leaderName)
                                            class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50">
                                        Declare from results
                                    </button>
                                </form>

                                <details class="rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3" @if ($errors->has('winner_note')) open @endif>
                                    <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">Choose the winner manually…</summary>
                                    <form method="POST" action="{{ route('competitions.winner.update', $competition) }}" class="mt-3 space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="method" value="manual">
                                        <select name="winner_id" required onchange="this.form.winner_type.value = this.options[this.selectedIndex].dataset.type"
                                                class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                            <option value="">Select the winner…</option>
                                            @foreach ($winnerOptions as $option)
                                                <option value="{{ $option['id'] }}" data-type="{{ $option['type'] }}">{{ $option['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="winner_type" value="{{ $winnerOptions->first()['type'] ?? '' }}">
                                        <textarea name="winner_note" rows="2" required minlength="10" maxlength="1000"
                                                  placeholder="Why the winner differs from the results, e.g. a disqualification or a judging decision"
                                                  class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">{{ old('winner_note') }}</textarea>
                                        <x-input-error :messages="$errors->get('winner_note')" />
                                        <button type="submit" class="rounded-xl bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                            Save manual winner
                                        </button>
                                    </form>
                                </details>
                            @endif
                        @endif
                    </div>

                    @if ($competition->registration_mode === 'team')
                        @if ($matchups->isEmpty())
                            <x-empty-state icon="🏁" class="py-8" message="No matchups have been posted yet." />
                        @else
                            <div class="mb-8 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                                            <th class="py-2 pr-2">#</th>
                                            <th class="py-2 pr-2">Team</th>
                                            <th class="py-2 px-1 text-right" title="Played">P</th>
                                            <th class="py-2 px-1 text-right" title="Won">W</th>
                                            <th class="py-2 px-1 text-right" title="Drawn">D</th>
                                            <th class="py-2 px-1 text-right" title="Lost">L</th>
                                            <th class="py-2 px-1 text-right" title="Goal difference">+/-</th>
                                            <th class="py-2 pl-1 text-right" title="Points">Pts</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                                        @foreach ($standings as $row)
                                            <tr>
                                                <td class="py-2 pr-2 text-gray-500">{{ $loop->iteration }}</td>
                                                <td class="py-2 pr-2 font-medium">{{ $row->team?->name ?? 'Unknown' }}</td>
                                                <td class="py-2 px-1 text-right">{{ $row->played }}</td>
                                                <td class="py-2 px-1 text-right">{{ $row->won }}</td>
                                                <td class="py-2 px-1 text-right">{{ $row->drawn }}</td>
                                                <td class="py-2 px-1 text-right">{{ $row->lost }}</td>
                                                <td class="py-2 px-1 text-right">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
                                                <td class="py-2 pl-1 text-right font-semibold">{{ $row->points }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">3 points for a win, 1 for a draw; ties broken by goal difference, then goals scored.</p>
                            </div>

                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($matchups as $matchup)
                                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                        <span class="text-gray-800 dark:text-gray-200">
                                            <span class="mr-2 text-xs font-semibold text-gray-500 dark:text-gray-400">R{{ $matchup->round }}{{ $matchup->played_on ? ' · '.$matchup->played_on->format('M j') : '' }}</span>
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
                                    Only confirmed teams can be entered into a matchup, and each team plays once per round.
                                </p>

                                @if (! $competition->acceptsResults())
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Matchups can be recorded once the competition has started ({{ $competition->start_time->format('M j, Y H:i') }}).</p>
                                @elseif ($confirmedTeams->count() < 2)
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">At least two confirmed teams are needed to record a matchup.</p>
                                @else
                                    <form method="POST" action="{{ route('competitions.matchups.store', $competition) }}"
                                          class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                        @csrf
                                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            Round
                                            <input type="number" name="round" min="1" max="1000" required value="{{ old('round', $nextRound) }}"
                                                   class="w-20 rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        </label>
                                        <input type="date" name="played_on" value="{{ old('played_on') }}" aria-label="Date played"
                                               class="rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
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
                            <x-empty-state icon="🏁" class="py-8" message="No results have been posted yet." />
                        @else
                            <x-leaderboard :results="$results" :competition="$competition" :winner="$competition->winner" />
                        @endif

                        @if ($canManage)
                            <div class="mt-10 border-t border-gray-100 dark:border-gray-700 pt-8">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Enter results</h2>
                                @php($resultFormat = $competition->sport->resultFormat())
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Only confirmed participants can be scored. Positions are calculated automatically.
                                    {{ $resultFormat->description() }}
                                </p>

                                @if (! $competition->acceptsResults())
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Results can be entered once the competition has started ({{ $competition->start_time->format('M j, Y H:i') }}).</p>
                                @elseif ($registrations->isEmpty())
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">No confirmed participants yet.</p>
                                @else
                                    <div class="mt-4 space-y-3">
                                        @foreach ($registrations as $registration)
                                            @php($existing = $results->get("{$registration->registrant_type}:{$registration->registrant_id}"))
                                            {{-- Old input and errors belong only to the row that was submitted. --}}
                                            @php($isSubmittedRow = old('registrant_type') === $registration->registrant_type && (int) old('registrant_id') === (int) $registration->registrant_id)
                                            <form method="POST" action="{{ route('competitions.results.store', $competition) }}"
                                                  class="rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3">
                                                @csrf
                                                <input type="hidden" name="registrant_type" value="{{ $registration->registrant_type }}">
                                                <input type="hidden" name="registrant_id" value="{{ $registration->registrant_id }}">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $registration->registrant?->name ?? 'Unknown' }}
                                                    </span>
                                                    <input type="text" name="value" required
                                                           inputmode="{{ $resultFormat->isTime() || $resultFormat->decimals > 0 ? 'decimal' : 'numeric' }}"
                                                           value="{{ $isSubmittedRow ? old('value') : $resultFormat->inputValue($existing?->value) }}"
                                                           placeholder="{{ $resultFormat->placeholder() }}"
                                                           aria-label="{{ $resultFormat->isTime() ? 'Time' : 'Score' }} for {{ $registration->registrant?->name ?? 'participant' }}"
                                                           class="w-32 rounded-xl border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                                    <button type="submit" class="rounded-xl bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                                        Save
                                                    </button>
                                                </div>
                                                @if ($isSubmittedRow)
                                                    <x-input-error :messages="$errors->get('value')" class="mt-2" />
                                                @endif
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
