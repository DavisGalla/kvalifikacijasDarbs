<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 py-12 px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('competitions.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to competitions
            </a>

            <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600">{{ $competition->sport->name }}</p>
                            <h1 class="mt-2 text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">
                                {{ $competition->title }}
                            </h1>
                        </div>
                        <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">
                            Published
                        </span>
                    </div>
                    <a href="{{ route('competitions.results.index', $competition) }}"
                       class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        View results
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                    @if ($competition->winner)
                        <p class="mt-4 text-sm font-semibold text-amber-700 dark:text-amber-400">
                            🏆 Winner: {{ $competition->winner->name }}
                        </p>
                    @endif
                </div>

                <div class="px-8 py-8">
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
                                        @if ($participant->status === 'pending')
                                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">
                                                Pending
                                            </span>
                                        @else
                                            <span class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-green-700">
                                                Confirmed
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @php($registration = $competition->registrations->first())
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
            </article>
        </div>
    </div>
</x-app-layout>
