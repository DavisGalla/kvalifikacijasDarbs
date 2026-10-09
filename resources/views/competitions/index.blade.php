<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

            {{-- Page heading --}}
            <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-gray-100 leading-tight tracking-tight">Competitions</h1>
                    <div class="mt-3 h-px w-16 bg-amber-400"></div>
                </div>
                <a href="{{ route('competitions.create') }}"
                   class="inline-flex items-center gap-2 bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150 w-fit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create competition
                </a>
            </div>

            @if ($competitions->isEmpty())
                <x-empty-state icon="🏆"
                    message="No upcoming competitions yet. Be the first to organise one."
                    cta="Create competition" :href="route('competitions.create')" />
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($competitions as $competition)
                        <x-card :href="route('competitions.show', $competition)">
                            <div class="flex items-start justify-between gap-3">
                                <x-sport-icon :sport="$competition->sport" />
                                <x-status-badge :status="$competition->displayStatus()" />
                            </div>

                            <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-gray-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150">
                                {{ $competition->title }}
                            </h3>
                            @if ($competition->sport)
                                <p class="mt-1 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $competition->sport->name }}</p>
                            @endif

                            <dl class="mt-5 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <dt class="sr-only">Date</dt>
                                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg>
                                    <dd>{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
                                </div>
                                <div class="flex items-center gap-2">
                                    <dt class="sr-only">Location</dt>
                                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Zm0-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                    <dd>{{ $competition->location }}</dd>
                                </div>
                                <div class="flex items-center gap-2">
                                    <dt class="sr-only">Signed up</dt>
                                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m5-2.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                                    <dd>{{ $competition->registrations_count }} {{ Str::plural($competition->registration_mode === 'team' ? 'team' : 'participant', $competition->registrations_count) }}</dd>
                                </div>
                            </dl>
                        </x-card>
                    @endforeach
                </div>

                <div class="mt-8">{{ $competitions->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
