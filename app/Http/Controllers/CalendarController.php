<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Models\User;
use App\Services\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
    
        if (!$user->google_access_token) {
            return redirect()->route('calendar.connect');
        }
    
        try {
            $service = new GoogleCalendarService($user);
            $events = $this->formatEvents($service->listEvents());
        } catch (\Exception $e) {
            Log::error('Calendar Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e
            ]);

            if ($this->requiresReconnect($e)) {
                return $this->reconnectGoogle($user);
            }

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

    public function create(): View|RedirectResponse
    {
        if (! auth()->user()->google_access_token) {
            return redirect()->route('calendar.connect');
        }

        return view('calendar.create');
    }

    /**
     * Explains what connecting Google Calendar does before sending the user to Google.
     */
    public function connect(): View|RedirectResponse
    {
        if (auth()->user()->google_access_token) {
            return redirect()->route('calendar.index');
        }

        return view('calendar.connect');
    }

    public function disconnect(GoogleCalendarConnection $connection): RedirectResponse
    {
        $connection->disconnect(auth()->user());

        return redirect()->route('calendar.connect')
            ->with('success', 'Google Calendar disconnected and access revoked.');
    }

    public function show(string $eventId): View|RedirectResponse
    {
        $user = auth()->user();

        // parabauda vai lietotājs ir atļavis piekļuvi pie google kalendāra
        if (!$user->google_access_token) {
            return redirect()->route('calendar.connect');
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

            if ($this->requiresReconnect($e)) {
                return $this->reconnectGoogle($user);
            }

            return redirect()->route('calendar.index')->with('error', 'Failed to load event details.');
        }
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->google_access_token) {
            return redirect()->route('calendar.connect');
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

            Cache::forget("dashboard.calendar.{$user->id}");

            return redirect()->route('calendar.index')->with('success', 'Event created successfully!');
        } catch (\Exception $e) {
            Log::error('Calendar Create Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            if ($this->requiresReconnect($e)) {
                return $this->reconnectGoogle($user);
            }

            return back()->withInput()->with('error', 'Failed to create event. Please try again.');
        }
    }

    public function destroy(string $eventId): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->google_access_token) {
            return redirect()->route('calendar.connect');
        }

        try {
            $service = new GoogleCalendarService($user);
            $service->deleteEvent($eventId);
            Cache::forget("dashboard.calendar.{$user->id}");

            return redirect()->route('calendar.index')->with('success', 'Event deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Calendar Delete Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'exception' => $e,
            ]);

            if ($this->requiresReconnect($e)) {
                return $this->reconnectGoogle($user);
            }

            return back()->with('error', 'Failed to delete event.');
        }
    }

    /**
     * A 401 from the Google API means the stored credentials are dead (expired
     * access token with no usable refresh token, or access revoked). There is
     * no way to recover without the user reconnecting their account.
     */
    private function requiresReconnect(\Exception $e): bool
    {
        return $e instanceof \App\Exceptions\GoogleCalendarDisconnectedException
            || ($e instanceof \Google\Service\Exception && $e->getCode() === 401);
    }

    private function reconnectGoogle(User $user): RedirectResponse
    {
        app(GoogleCalendarConnection::class)->forget($user);

        return redirect()->route('calendar.connect')
            ->with('error', 'Your Google Calendar connection has expired. Please reconnect.');
    }
}
