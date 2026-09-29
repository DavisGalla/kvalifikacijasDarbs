<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 flex items-start justify-center pt-20 px-4">
        <div class="w-full max-w-2xl">
            <x-back-link :href="route('teams.index')">Back to teams</x-back-link>
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create a team</h1>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Build a team and choose who can join.</p>
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

                <form method="POST" action="{{ route('teams.store') }}" class="px-8 py-8 space-y-5">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-semibold tracking-widest uppercase text-gray-400 dark:text-gray-500 mb-2">Team name</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}"
                               class="w-full px-4 py-3 text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
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
                    <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public')) class="mt-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-amber-500 focus:ring-amber-400">
                        <span><strong class="font-semibold">Publish this team</strong><br><span class="text-gray-500 dark:text-gray-400">Anyone can find and join this team.</span></span>
                    </label>
                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('teams.index') }}" class="flex-1 text-center px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-sm text-gray-500 dark:text-gray-400 font-semibold hover:border-gray-400 dark:hover:border-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">Cancel</a>
                        <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold hover:bg-gray-700 dark:hover:bg-gray-600 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150">Create team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
