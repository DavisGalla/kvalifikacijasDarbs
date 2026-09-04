<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-2xl">
            {{-- back link --}}
            <a href="{{ route('calendar.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to calendar
            </a>

            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">
                        {{ $eventData['title'] }}
                    </h1>
                </div>

                <div class="px-8 py-8 space-y-5 text-sm">
                    <div>
                        <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-1">Start</p>
                        <p class="text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($eventData['start'])->format('l, F j, Y H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-1">End</p>
                        <p class="text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($eventData['end'])->format('l, F j, Y H:i') }}</p>
                    </div>

                    @if(!empty($eventData['location']))
                        <div>
                            <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-1">Location</p>
                            <p class="text-gray-900 dark:text-gray-100">{{ $eventData['location'] }}</p>
                        </div>
                    @endif

                    @if(!empty($eventData['description']))
                        <div>
                            <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-1">Description</p>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $eventData['description'] }}</p>
                        </div>
                    @endif

                    <div class="pt-3">
                        <form method="POST" action="{{ route('calendar.destroy', $eventData['id']) }}" onsubmit="return confirm('Delete this event?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-500 active:scale-95 transition-all duration-150">
                                Delete Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
