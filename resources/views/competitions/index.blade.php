<x-app-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				Competitions
			</h2>
			<a href="{{ route('competitions.create') }}"
			   class="rounded-full bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
				Create competition
			</a>
		</div>
	</x-slot>

	<div class="py-12">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

			@if ($competitions->isEmpty())
				<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-600">
					No upcoming competitions are available right now.
				</div>
			@else
				<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
					@foreach ($competitions as $competition)
						<a href="{{ route('competitions.show', $competition) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
							<div class="flex items-start justify-between gap-4">
								<h3 class="text-xl font-semibold text-gray-900">
									{{ $competition->title }}
								</h3>
								@if ($competition->sport)
									<span class="text-sm text-indigo-600 whitespace-nowrap">
										{{ $competition->sport->name }}
									</span>
								@endif
							</div>

							<dl class="mt-5 space-y-2 text-sm text-gray-600">
								<div>
									<dt class="inline font-medium text-gray-900">Start:</dt>
									<dd class="inline">{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
								</div>
								<div>
									<dt class="inline font-medium text-gray-900">Place:</dt>
									<dd class="inline">{{ $competition->location }}</dd>
								</div>
							</dl>
						</a>
					@endforeach
				</div>
			@endif
		</div>
	</div>
</x-app-layout>