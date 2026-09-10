<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Competition history') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">
                    Your registrations
                </p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Competition history
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Every competition you have signed up for, including competitions you later left or that were canceled.
                </p>
            </div>

            @if ($registrations->isEmpty())
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm rounded-xl p-8 text-center">
                    <p class="text-gray-600 dark:text-gray-400">You have not signed up for any competitions yet.</p>
                    <a href="{{ route('competitions.index') }}" class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">
                        Browse competitions
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($registrations as $registration)
                        @php($competition = $registration->competition)
                        <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm rounded-xl p-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    @if ($competition->sport)
                                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $competition->sport->name }}</p>
                                    @endif
                                    <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $competition->title }}</h2>
                                    <dl class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <div>
                                            <dt class="inline font-semibold">Competition:</dt>
                                            <dd class="inline">{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="inline font-semibold">Registered:</dt>
                                            <dd class="inline">{{ $registration->registered_at->format('M j, Y g:i A') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="inline font-semibold">Location:</dt>
                                            <dd class="inline">{{ $competition->location }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                                    @php($isCompetitionCancelled = $competition->status === 'cancelled')
                                    @php($isCompetitionFinished = $competition->end_time->isPast())
                                    @php($isRegistrationCancelled = $registration->status === 'cancelled')
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $isCompetitionCancelled || $isCompetitionFinished || $isRegistrationCancelled ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                                        {{ $isCompetitionCancelled ? 'Canceled' : ($isRegistrationCancelled ? 'Left' : ($isCompetitionFinished ? 'Finished' : $registration->status)) }}
                                    </span>
                                    <a href="{{ route('competitions.show', $competition) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                        View competition
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
