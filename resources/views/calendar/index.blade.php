<x-app-layout>
    <div class="min-h-screen bg-slate-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

            {{-- Page heading --}}
            <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-serif font-bold text-white leading-tight tracking-tight">Calendar</h1>
                    <p class="mt-2 text-sm text-slate-200">Click a day to add training. Click an event to view details.</p>
                    <div class="mt-3 h-px w-16 bg-amber-400"></div>
                </div>
                <a href="{{ route('calendar.create') }}"
                   class="inline-flex items-center gap-2 bg-gray-700 text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-gray-600 active:scale-95 transition-all duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Event
                </a>
            </div>

            {{-- Calendar card --}}
            <div class="rounded-3xl border border-slate-700 shadow-sm p-4 sm:p-6 lg:p-8">
                <div id="calendar"></div>
            </div>

        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
    @endpush
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <script>
        const events = @json($events);
        const createUrl = "{{ route('calendar.create') }}";
        const showUrlTemplate = "{{ route('calendar.show', ['eventId' => '__EVENT_ID__']) }}";

        document.addEventListener('DOMContentLoaded', function () {
            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                timeZone: 'Europe/Riga',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                height: 'auto',
                events: events,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                dateClick: function (info) {
                    window.location.href = createUrl + '?date=' + info.dateStr;
                },
                eventClick: function (info) {
                    const eventId = info.event.id;
                    if (!eventId) return;

                    window.location.href = showUrlTemplate.replace('__EVENT_ID__', encodeURIComponent(eventId));
                },
            });
            calendar.render();
        });
    </script>
</x-app-layout>