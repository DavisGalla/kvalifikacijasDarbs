<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-2xl">
            <a href="{{ route('competitions.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-150 mb-8 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to competitions
            </a>

            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">Create a competition</h1>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Add the details for your upcoming event.</p>
                </div>

                @if ($errors->any())
                    <div class="mx-8 mt-6 rounded-xl bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('competitions.store') }}" class="px-8 py-8 space-y-5">
                    @csrf

                    <div>
                        <label for="title" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Title</label>
                        <input type="text" name="title" id="title" required value="{{ old('title') }}" placeholder="e.g. City Marathon"
                               class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                    </div>

                    <div>
                        <label for="sport_id" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Sport</label>
                        <select name="sport_id" id="sport_id" required
                                class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                            <option value="">Select a sport</option>
                            @foreach ($sports as $sport)
                                <option value="{{ $sport->id }}" @selected(old('sport_id') == $sport->id)>{{ $sport->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Description</label>
                        <textarea name="description" id="description" required rows="4" placeholder="Tell people what to expect..."
                                  class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="location" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Location</label>
                        <input type="text" name="location" id="location" required value="{{ old('location') }}" placeholder="e.g. Riverside Park"
                               class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="start_time" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Start time</label>
                            <input type="datetime-local" name="start_time" id="start_time" required value="{{ old('start_time') }}"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>
                        <div>
                            <label for="end_time" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">End time</label>
                            <input type="datetime-local" name="end_time" id="end_time" required value="{{ old('end_time') }}"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="registration_deadline" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Registration deadline</label>
                            <input type="datetime-local" name="registration_deadline" id="registration_deadline" required value="{{ old('registration_deadline') }}"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>
                        <div>
                            <label for="max_participants" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Maximum participants</label>
                            <input type="number" name="max_participants" id="max_participants" min="1" value="{{ old('max_participants') }}" placeholder="Optional"
                                   class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-300 dark:placeholder-gray-600 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Status</label>
                        <select name="status" id="status" required
                                class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                            <option value="published" @selected(old('status') === 'published')>Published</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('competitions.index') }}"
                           class="flex-1 text-center px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-sm text-gray-500 dark:text-gray-400 font-semibold hover:border-gray-400 dark:hover:border-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-4 py-3 rounded-xl bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold hover:bg-gray-700 dark:hover:bg-gray-600 active:scale-95 transition-all duration-150">
                            Create competition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
