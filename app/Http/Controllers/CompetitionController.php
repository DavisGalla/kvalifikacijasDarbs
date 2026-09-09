<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Models\Competition;
use App\Models\Sport;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::with('sport')
            ->with(['registrations' => function ($query) {
                $query->where('registrant_type', 'user')
                    ->where('registrant_id', Auth::id());
            }])
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        return view('competitions.index', compact('competitions'));
    }

    public function show(Competition $competition): View
    {
        abort_unless($competition->status === 'published', 404);

        $competition->load('sport', 'organizer');
        $competition->load(['registrations' => function ($query) {
            $query->where('registrant_type', 'user')
                ->where('registrant_id', Auth::id());
        }]);

        return view('competitions.show', compact('competition'));
    }

    public function create(): View
    {
        $sports = Sport::orderBy('name')->get();

        return view('competitions.create', compact('sports'));
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        Competition::create([
            'organizer_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return redirect()
            ->route('competitions.index')
            ->with('success', 'Competition created successfully.');
    }

    public function register(Competition $competition): RedirectResponse
    {
        if ($competition->status !== 'published') {
            return back()->with('error', 'This competition is not open for registration.');
        }

        if (now()->greaterThanOrEqualTo($competition->registration_deadline)) {
            return back()->with('error', 'Registration for this competition has closed.');
        }

        $result = DB::transaction(function () use ($competition): string {
            $existingRegistration = $competition->registrations()
                ->where('registrant_type', 'user')
                ->where('registrant_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (
                in_array($existingRegistration?->status, ['pending', 'confirmed'], true)
                && $existingRegistration->google_event_id
            ) {
                return 'already_registered';
            }

            $activeRegistrations = $competition->registrations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->count();

            if ($competition->max_participants !== null && $activeRegistrations >= $competition->max_participants) {
                return 'full';
            }

            if ($existingRegistration) {
                $existingRegistration->update([
                    'status' => 'pending',
                    'registered_at' => now(),
                ]);

                return 'registered';
            }

            $competition->registrations()->create([
                'registrant_type' => 'user',
                'registrant_id' => Auth::id(),
                'status' => 'pending',
                'registered_at' => now(),
            ]);

            return 'registered';
        });

        if ($result === 'already_registered') {
            return back()->with('error', 'You are already registered for this competition.');
        }

        if ($result === 'full') {
            return back()->with('error', 'This competition has reached its participant limit.');
        }

        $registration = $competition->registrations()
            ->where('registrant_type', 'user')
            ->where('registrant_id', Auth::id())
            ->first();

        if (! Auth::user()->google_access_token) {
            return back()->with('success', 'Registration submitted successfully. Connect Google Calendar to add it to your calendar.');
        }

        try {
            $event = (new GoogleCalendarService(Auth::user()))->createEvent(
                $competition->title,
                $competition->description . "\n\nLocation: " . $competition->location,
                $competition->start_time,
                $competition->end_time,
            );

            $registration->update(['google_event_id' => $event->getId()]);
        } catch (\Throwable $exception) {
            Log::warning('Competition registration calendar event failed.', [
                'user_id' => Auth::id(),
                'competition_id' => $competition->id,
                'exception' => $exception,
            ]);

            return back()->with('error', 'Registration submitted, but the Google Calendar event could not be created.');
        }

        return back()->with('success', 'Registration submitted successfully.');
    }

    public function cancelRegistration(Competition $competition): RedirectResponse
    {
        $registration = $competition->registrations()
            ->where('registrant_type', 'user')
            ->where('registrant_id', Auth::id())
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (! $registration) {
            return back()->with('error', 'You are not registered for this competition.');
        }

        if (now()->greaterThanOrEqualTo($competition->start_time)) {
            return back()->with('error', 'You cannot leave a competition after it has started.');
        }

        if ($registration->google_event_id && Auth::user()->google_access_token) {
            try {
                (new GoogleCalendarService(Auth::user()))->deleteEvent($registration->google_event_id);
            } catch (\Throwable $exception) {
                Log::warning('Competition registration calendar event deletion failed.', [
                    'user_id' => Auth::id(),
                    'competition_id' => $competition->id,
                    'event_id' => $registration->google_event_id,
                    'exception' => $exception,
                ]);
            }
        }

        $registration->update([
            'status' => 'cancelled',
            'google_event_id' => null,
        ]);

        return back()->with('success', 'You have left the competition.');
    }
}
