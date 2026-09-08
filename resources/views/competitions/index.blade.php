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
			@if (session('success'))
				<div class="mb-6 rounded-lg bg-green-50 p-4 text-green-700">
					{{ session('success') }}
				</div>
			@endif

			@if ($competitions->isEmpty())
				<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-600">
					No upcoming competitions are available right now.
				</div>
			@else
				<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
					@foreach ($competitions as $competition)
						<article class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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

							<p class="mt-3 text-gray-600">
								{{ $competition->description }}
							</p>

							<dl class="mt-5 space-y-2 text-sm text-gray-600">
								<div>
									<dt class="inline font-medium text-gray-900">When:</dt>
									<dd class="inline">{{ $competition->start_time->format('M j, Y g:i A') }}</dd>
								</div>
								<div>
									<dt class="inline font-medium text-gray-900">Where:</dt>
									<dd class="inline">{{ $competition->location }}</dd>
								</div>
								<div>
									<dt class="inline font-medium text-gray-900">Registration closes:</dt>
									<dd class="inline">{{ $competition->registration_deadline->format('M j, Y g:i A') }}</dd>
								</div>
								@if ($competition->max_participants)
									<div>
										<dt class="inline font-medium text-gray-900">Participants:</dt>
										<dd class="inline">Up to {{ $competition->max_participants }}</dd>
									</div>
								@endif
							</dl>
						</article>
					@endforeach
				</div>
			@endif
		</div>
	</div>
</x-app-layout>