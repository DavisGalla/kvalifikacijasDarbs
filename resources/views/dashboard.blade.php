<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-10">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">
            Welcome back
        </p>
        <h1 class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100 leading-tight">
            {{ auth()->user()->name }}
        </h1>

        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            {{-- Stat: Personal bests --}}
            <a href="{{ route('pbs.index') }}" class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['personalBests'] }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personal bests logged</p>
                <p class="mt-3 text-sm font-semibold text-amber-600 dark:text-amber-400 inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </p>
            </a>

            {{-- Stat: Upcoming competitions --}}
            <a href="{{ route('competitions.history') }}" class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 1h13l-2 3.5L17 12H4" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['upcomingCompetitions'] }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upcoming competitions</p>
                <p class="mt-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400 inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                    View history
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </p>
            </a>

            {{-- Stat: Teams --}}
            <a href="{{ route('teams.index') }}" class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m5-2.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-2a4 4 0 1 0 0-8m-14 8a4 4 0 1 1 0-8" />
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['teams'] }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Teams joined</p>
                <p class="mt-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                    View teams
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </p>
            </a>

            {{-- Google Calendar connection --}}
            @if(auth()->user()->google_access_token)
                <div class="rounded-2xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/10 p-6">
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-green-600 dark:text-green-400 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                    <p class="mt-4 font-semibold text-green-800 dark:text-green-400">Calendar connected</p>
                    <p class="mt-1 text-sm text-green-700/80 dark:text-green-400/70">Click a day in the calendar to plan a session.</p>
                    <a href="{{ route('calendar.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-green-700 dark:text-green-400 hover:gap-2 transition-all">
                        Open calendar
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @else
                <div class="rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/10 p-6">
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                        </svg>
                    </div>
                    <p class="mt-4 font-semibold text-amber-800 dark:text-amber-400">Connect Google Calendar</p>
                    <p class="mt-1 text-sm text-amber-700/80 dark:text-amber-400/70">One-time setup to start planning sessions.</p>
                    <a href="{{ route('calendar.connect') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-amber-700 dark:text-amber-400 hover:gap-2 transition-all">
                        Connect now
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @endif
        </div>

        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Recent personal bests with trend --}}
            <x-card>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Personal bests</h2>
                    <a href="{{ route('pbs.index') }}" class="text-sm font-semibold text-amber-600 dark:text-amber-400 hover:underline">View all</a>
                </div>

                @if ($recentBests->isEmpty())
                    <x-empty-state icon="🏋️" class="py-8" message="No personal bests yet. Log your first lift to start tracking progress."
                        cta="Add a PB" :href="route('pbs.index')" />
                @else
                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($recentBests as $best)
                            <li class="flex items-center gap-4 py-3">
                                <p class="flex-1 min-w-0 truncate font-medium text-gray-900 dark:text-gray-100">{{ $best->exercise }}</p>
                                <x-sparkline :values="$best->entries->pluck('weight')" :width="72" :height="24" />
                                <div class="flex items-baseline gap-1.5 w-28 justify-end">
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $best->weight + 0 }}</span>
                                    <span class="text-xs text-gray-400">kg</span>
                                    <x-trend :delta="$best->trend()" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Upcoming calendar events --}}
            <x-card>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upcoming events</h2>
                    @if ($calendarConnected)
                        <a href="{{ route('calendar.index') }}" class="text-sm font-semibold text-amber-600 dark:text-amber-400 hover:underline">Open calendar</a>
                    @endif
                </div>

                @if (! $calendarConnected)
                    <x-empty-state icon="📅" class="py-8" message="Connect Google Calendar to see your upcoming sessions and events here."
                        cta="Connect Google Calendar" :href="route('calendar.connect')" />
                @elseif ($calendarError)
                    <x-empty-state icon="⚠️" class="py-8" message="We couldn't load your calendar right now. Try again in a moment, or reconnect if it keeps happening."
                        cta="Reconnect" :href="route('calendar.connect')" />
                @elseif (count($calendarEvents) === 0)
                    <x-empty-state icon="📅" class="py-8" message="Nothing scheduled. Plan your next training session."
                        cta="Add an event" :href="route('calendar.create')" />
                @else
                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($calendarEvents as $event)
                            @php($start = \Carbon\Carbon::parse($event['start']))
                            <li>
                                <a href="{{ route('calendar.show', $event['id']) }}" class="flex items-center gap-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 -mx-2 px-2 rounded-lg transition">
                                    <div class="w-12 shrink-0 rounded-xl bg-amber-50 dark:bg-amber-900/20 py-1.5 text-center">
                                        <p class="text-[10px] font-semibold uppercase text-amber-600 dark:text-amber-400">{{ $start->format('M') }}</p>
                                        <p class="text-lg font-bold leading-none text-gray-900 dark:text-gray-100">{{ $start->format('j') }}</p>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $event['title'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $event['all_day'] ? 'All day' : $start->format('D, g:i A') }}</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        {{-- Quick links --}}
        <div class="mt-10 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Keep the momentum going</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-2xl">
                Track your sport progress, plan training in Google Calendar, and share updates with the community forum.
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('competitions.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-150">
                    Browse competitions
                </a>
                <a href="{{ route('blog.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Visit the blog
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
