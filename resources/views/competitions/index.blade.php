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
                <div class="text-center py-20">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4 text-2xl">🏆</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500">No upcoming competitions are available right now.</p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($competitions as $competition)
                        <a href="{{ route('competitions.show', $competition) }}"
                           class="group block bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150">
                                    {{ $competition->title }}
                                </h3>
                                @if ($competition->sport)
                                    <span class="shrink-0 rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                        {{ $competition->sport->name }}
                                    </span>
                                @endif
                            </div>

                            <dl class="mt-5 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div>
                                    <dt class="inline font-medium text-gray-900 dark:text-gray-200">Start:</dt>
                                    <dd class="inline">{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="inline font-medium text-gray-900 dark:text-gray-200">Place:</dt>
                                    <dd class="inline">{{ $competition->location }}</dd>
                                </div>
                                <div>
                                    <dt class="inline font-medium text-gray-900 dark:text-gray-200">Signed up:</dt>
                                    <dd class="inline">{{ $competition->registrations_count }} {{ Str::plural($competition->registration_mode === 'team' ? 'team' : 'participant', $competition->registrations_count) }}</dd>
                                </div>
                            </dl>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
