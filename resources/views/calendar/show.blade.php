<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-2xl">
            <x-back-link :href="route('calendar.index')">Back to calendar</x-back-link>

            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
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
                                    class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-500 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150">
                                Delete Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
