<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 py-12 px-4">
        <div class="max-w-3xl mx-auto">
            <x-back-link :href="route('competitions.index')">Back to competitions</x-back-link>

            <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $competition->sport->name }}</p>
                            <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $competition->title }}
                            </h1>
                        </div>
                        <x-status-badge :status="$competition->displayStatus()" />
                    </div>
                    @if ($competition->winner)
                        <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-50 dark:bg-amber-900/20 px-3 py-1 text-sm font-semibold text-amber-700 dark:text-amber-400">
                            🏆 Winner: {{ $competition->winner->name }}
                        </p>
                    @endif
                </div>

                <div x-data="{ tab: window.location.hash === '#results' ? 'results' : 'details' }">
                <div class="flex gap-6 px-8 border-b border-gray-100 dark:border-gray-700" role="tablist">
                    <button type="button" role="tab" @click="tab = 'details'"
                            :class="tab === 'details' ? 'border-amber-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">Details</button>
                    <button type="button" role="tab" @click="tab = 'results'"
                            :class="tab === 'results' ? 'border-amber-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="-mb-px border-b-2 py-3 text-sm font-semibold transition-colors">Results</button>
                </div>

                <div x-show="tab === 'results'" x-cloak class="px-8 py-8" id="results">
                    @if ($results->isNotEmpty())
                        <x-leaderboard :results="$results" :competition="$competition" :winner="$competition->winner" />
                    @elseif ($competition->registration_mode === 'team' && $matchups->isNotEmpty())
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($matchups as $matchup)
                                <li class="py-3 text-sm text-gray-800 dark:text-gray-200">
                                    {{ $matchup->homeTeam?->name ?? 'Unknown' }}
                                    <span class="mx-2 font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $matchup->home_score }} – {{ $matchup->away_score }}</span>
                                    {{ $matchup->awayTeam?->name ?? 'Unknown' }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-empty-state icon="🏁" class="py-8" message="No results have been posted yet." />
                    @endif
                    <a href="{{ route('competitions.results.index', $competition) }}"
                       class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        {{ $canManage ? 'Manage results' : 'Full results page' }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>

                <div x-show="tab === 'details'" class="px-8 py-8">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ $competition->description }}
                    </p>

                    <dl class="mt-8 grid gap-5 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Place</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->location }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Starts</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Ends</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->end_time->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Registration closes</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->registration_deadline->format('M j, Y g:i A') }}</dd>
                        </div>
                        @if ($competition->max_participants)
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-gray-100">Maximum participants</dt>
                                <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->max_participants }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Registration type</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">
                                {{ $competition->registration_mode === 'team' ? 'Team captains register their whole team' : 'Individual registration' }}
                            </dd>
                        </div>
                        @if ($competition->registration_mode === 'team' && ($competition->min_team_members || $competition->max_team_members))
                            <div>
                                <dt class="font-semibold text-gray-900 dark:text-gray-100">Team size</dt>
                                <dd class="mt-1 text-gray-600 dark:text-gray-400">
                                    @if ($competition->min_team_members && $competition->max_team_members)
                                        {{ $competition->min_team_members }}–{{ $competition->max_team_members }} members
                                    @elseif ($competition->min_team_members)
                                        At least {{ $competition->min_team_members }} members
                                    @else
                                        Up to {{ $competition->max_team_members }} members
                                    @endif
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-semibold text-gray-900 dark:text-gray-100">Organizer</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $competition->organizer->name }}</dd>
                        </div>
                    </dl>

                    <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Who's signed up
                            <span class="font-normal text-gray-500 dark:text-gray-400">({{ $participants->count() }})</span>
                        </h2>

                        @if ($participants->isEmpty())
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">No one has registered yet. Be the first!</p>
                        @else
                            <ul class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($participants as $participant)
                                    <li class="flex items-center justify-between gap-4 py-2.5 text-sm">
                                        <span class="text-gray-800 dark:text-gray-200">
                                            {{ $participant->registrant->name }}
                                            @if ($participant->registrant_type === 'team')
                                                <span class="text-gray-400 dark:text-gray-500">(team)</span>
                                            @endif
                                        </span>
                                        <x-status-badge :status="$participant->status" />
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @php($registration = $competition->registrations->first(fn ($registration) => in_array($registration->status, ['pending', 'confirmed'])))
                    <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                        @if ($registration && in_array($registration->status, ['pending', 'confirmed']))
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm font-semibold text-green-700">
                                    {{ $registration->registrant_type === 'team' ? 'Your team is' : 'You are' }} {{ $registration->status }}.
                                </p>
                                <form method="POST" action="{{ route('competitions.registration.cancel', $competition) }}">
                                    @csrf
                                    @method('DELETE')
                                    @if ($registration->registrant_type === 'team')
                                        <input type="hidden" name="team_id" value="{{ $registration->registrant_id }}">
                                    @endif
                                    <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                                        Leave competition
                                    </button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('competitions.register', $competition) }}" class="space-y-3">
                                @csrf
                                @if ($competition->registration_mode === 'team')
                                    @if ($teams->isEmpty())
                                        <p class="text-sm text-gray-600 dark:text-gray-400">You need to captain a team for {{ $competition->sport->name }} before you can register.</p>
                                    @else
                                        <label for="team_id" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Choose your team</label>
                                        <select name="team_id" id="team_id" required class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                            <option value="">Select a team</option>
                                            @foreach ($teams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                @endif
                                <button type="submit" @disabled($competition->registration_mode === 'team' && $teams->isEmpty()) class="w-full rounded-xl bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    {{ $competition->registration_mode === 'team' ? 'Register team for competition' : 'Register for competition' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
