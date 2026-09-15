<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900 py-12 px-4">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('teams.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 mb-8">Back to teams</a>
            <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600">{{ $team->sport->name }}</p>
                            <h1 class="mt-2 text-3xl font-serif font-bold text-gray-900 dark:text-gray-100">{{ $team->name }}</h1>
                            <p class="mt-2 text-sm text-gray-500">Captain: {{ $team->captain->name }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $team->is_public ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $team->is_public ? 'Public' : 'Invite only' }}
                        </span>
                    </div>
                </div>

                <div class="px-8 py-8">
                    @if ($team->is_public && ! $isMember)
                        <form method="POST" action="{{ route('teams.join', $team) }}" class="mb-8">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-700">Join team</button>
                        </form>
                    @elseif ($isMember && $team->captain_id !== auth()->id())
                        <form method="POST" action="{{ route('teams.leave', $team) }}" class="mb-8">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Leave team</button>
                        </form>
                    @endif

                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Members ({{ $team->members->count() }})</h2>
                    <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($team->members as $member)
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $member->user->name }}</span>
                                <span class="text-xs font-semibold uppercase text-gray-400">{{ $member->role }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($team->captain_id === auth()->id())
                        <div class="mb-8 flex justify-end">
                            <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Delete team</button>
                            </form>
                        </div>

                        <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Invite someone</h2>
                            <form method="POST" action="{{ route('teams.invite', $team) }}" class="mt-4 flex gap-3">
                                @csrf
                                <input type="text" name="username" required placeholder="username" class="min-w-0 flex-1 px-4 py-3 text-gray-900 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <button type="submit" class="rounded-xl bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-700">Invite</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
