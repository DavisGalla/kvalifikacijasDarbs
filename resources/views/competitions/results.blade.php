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
