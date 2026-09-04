<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-lg">

            {{-- Back link --}}
            <a href="{{ route('calendar.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to calendar
            </a>

            {{-- Card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">

                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">Add Event</h1>
                    @if(request('date'))
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse(request('date'))->format('l, F j, Y') }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Fill in the details below.</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('calendar.store') }}" class="px-8 py-8 space-y-5">
                    @csrf

                    <div>
                        <label for="summary" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">
                            Event Title <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="summary" id="summary"
                               required
                               value="{{ old('summary') }}"
                               placeholder="e.g. Morning run"
                               class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition @error('summary') border-red-400 @enderror">
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">
                            Description
                        </label>
                        <textarea name="description" id="description"
                                  rows="3"
                                  placeholder="Optional details..."
                                  class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_time" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">
                                Start <span class="text-red-400">*</span>
                            </label>
                            <input type="datetime-local" name="start_time" id="start_time"
                                   required
                                   value="{{ old('start_time', request('date') ? request('date').'T09:00' : '') }}"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition @error('start_time') border-red-400 @enderror">
                        </div>
                        <div>
                            <label for="end_time" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">
                                End <span class="text-red-400">*</span>
                            </label>
                            <input type="datetime-local" name="end_time" id="end_time"
                                   required
                                   value="{{ old('end_time', request('date') ? request('date').'T10:00' : '') }}"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition @error('end_time') border-red-400 @enderror">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('calendar.index') }}"
                           class="flex-1 text-center px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-sm text-gray-500 dark:text-gray-400 font-semibold hover:border-gray-400 dark:hover:border-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-4 py-3 rounded-xl bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150">
                            Create Event
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>