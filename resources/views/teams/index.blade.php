<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teams</h2>
            <a href="{{ route('teams.create') }}" class="rounded-full bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                Create team
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($invitations->isNotEmpty())
                <section class="mb-8 rounded-2xl bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Team invitations</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <p class="text-sm text-gray-700">
                                    <strong>{{ $invitation->inviter->name }}</strong> invited you to join <strong>{{ $invitation->team->name }}</strong>.
                                </p>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('teams.invitations.accept', $invitation) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('teams.invitations.decline', $invitation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Decline</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($teams->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-600">
                    No teams are available yet.
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teams as $team)
                        <a href="{{ route('teams.show', $team) }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-xl font-semibold text-gray-900">{{ $team->name }}</h3>
                                @if ($team->is_public)
                                    <span class="text-sm text-green-600">Public</span>
                                @else
                                    <span class="text-sm text-gray-500">Invite only</span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm text-indigo-600">{{ $team->sport->name }}</p>
                            <p class="mt-2 text-sm text-gray-600">Captain: {{ $team->captain->name }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ $team->members_count }} {{ Str::plural('member', $team->members_count) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
