<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
    
        if (!$user->google_access_token) {
            return redirect()->route('google.redirect')->with('error', 'Please connect your Google Calendar first.');
        }
    
        try {
            $service = new GoogleCalendarService($user);
            $events = $this->formatEvents($service->listEvents());
        } catch (\Exception $e) {
            Log::error('Calendar Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return back()->with('error', 'Failed to retrieve calendar events.');
        }
    
        return view('calendar.index', compact('events'));
    }
    
    private function formatEvents(array $rawEvents): array
    {
        return collect($rawEvents)->map(fn($e) => [
            'id' => $e->getId(),
            'title' => $e->getSummary() ?? '(No title)',
            'start' => $e->getStart()->dateTime ?? $e->getStart()->date,
            'end'   => $e->getEnd()->dateTime ?? $e->getEnd()->date,
        ])->values()->toArray();
    }

    public function create(): View
    {
        return view('calendar.create');
    }

    public function show(string $eventId): View|RedirectResponse
    {
        $user = auth()->user();

        // parabauda vai lietotājs ir atļavis piekļuvi pie google kalendāra
        if (!$user->google_access_token) {
            return redirect()->route('google.redirect')->with('error', 'Please connect your Google Calendar first.');
        }

        try {
            $service = new GoogleCalendarService($user);
            $event = $service->getEvent($eventId);

            $eventData = [
                'id' => $event->getId(),
                'title' => $event->getSummary() ?? '(No title)',
                'description' => $event->getDescription(),
                'start' => $event->getStart()->dateTime ?? $event->getStart()->date,
                'end' => $event->getEnd()->dateTime ?? $event->getEnd()->date,
                'location' => $event->getLocation(),
            ];

            return view('calendar.show', compact('eventData'));
        } catch (\Exception $e) {
            Log::error('Calendar Show Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'exception' => $e,
            ]);

            return redirect()->route('calendar.index')->with('error', 'Failed to load event details.');
        }
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->google_access_token) {
            return redirect()->route('google.redirect')->with('error', 'Please connect your Google Calendar first.');
        }

        try {
            $service = new GoogleCalendarService($user);
            $validated = $request->validated();
            $event = $service->createEvent(
                $validated['summary'],
                $validated['description'],
                $validated['start_time'],
                $validated['end_time']
            );

            return redirect()->route('calendar.index')->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create event: ' . $e->getMessage());
        }
    }

    public function destroy(string $eventId): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->google_access_token) {
            return redirect()->route('google.redirect')->with('error', 'Please connect your Google Calendar first.');
        }

        try {
            $service = new GoogleCalendarService($user);
            $service->deleteEvent($eventId);

            return redirect()->route('calendar.index')->with('success', 'Event deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Calendar Delete Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to delete event.');
        }
    }
}
