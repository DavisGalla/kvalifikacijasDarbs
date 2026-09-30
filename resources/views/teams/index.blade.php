<x-app-layout>
    <div class="min-h-screen bg-stone-50 dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

            {{-- Page heading --}}
            <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-gray-100 leading-tight tracking-tight">Teams</h1>
                    <div class="mt-3 h-px w-16 bg-amber-400"></div>
                </div>
                <a href="{{ route('teams.create') }}"
                   class="inline-flex items-center gap-2 bg-gray-800 dark:bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-gray-700 dark:hover:bg-gray-600 shadow-sm hover:shadow-md active:scale-95 transition-all duration-150 w-fit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create team
                </a>
            </div>

            @if ($invitations->isNotEmpty())
                <section class="mb-8 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Team invitations</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0 last:pb-0">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <strong>{{ $invitation->inviter->name }}</strong> invited you to join <strong>{{ $invitation->team->name }}</strong>.
                                    <x-status-badge :status="$invitation->status" class="ml-2" />
                                </p>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('teams.invitations.accept', $invitation) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-gray-800 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:hover:bg-gray-600 transition">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('teams.invitations.decline', $invitation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-red-200 dark:border-red-800 px-3 py-2 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">Decline</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($teams->isEmpty())
                <x-empty-state icon="👥" message="No teams yet. Create one and invite your training partners." cta="Create team" :href="route('teams.create')" />
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teams as $team)
                        <x-card :href="route('teams.show', $team)">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150">
                                    {{ $team->name }}
                                </h3>
                                @if ($team->is_public)
                                    <x-status-badge color="green">Public</x-status-badge>
                                @else
                                    <x-status-badge color="gray">Invite only</x-status-badge>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $team->sport->name }}</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Captain: {{ $team->captain->name }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $team->members_count }} {{ Str::plural('member', $team->members_count) }}</p>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
