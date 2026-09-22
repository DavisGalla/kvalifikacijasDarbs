<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\Team;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->withCount(['registrations' => function ($query) {
                $query->whereIn('status', ['pending', 'confirmed']);
            }])
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        return view('competitions.index', compact('competitions'));
    }

    public function history(): View
    {
        $registrations = Registration::query()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('registrant_type', 'user')
                        ->where('registrant_id', Auth::id());
                })->orWhere(function ($query) {
                    $query->where('registrant_type', 'team')
                        ->whereHas('registrant', fn ($query) => $query->where('captain_id', Auth::id()));
                });
            })
            ->with(['competition.sport'])
            ->latest('registered_at')
            ->get();

        return view('competitions.history', compact('registrations'));
    }

    public function show(Competition $competition): View
    {
        abort_unless($competition->status === 'published', 404);

        $competition->load('sport', 'organizer', 'winner');
        $competition->load(['registrations' => function ($query) {
            $query->where(function ($query) {
                $query->where('registrant_type', 'user')
                    ->where('registrant_id', Auth::id())
                    ->orWhere(function ($query) {
                        $query->where('registrant_type', 'team')
                            ->whereHas('registrant', fn ($query) => $query->where('captain_id', Auth::id()));
                    });
            });
        }]);

        $teams = $competition->registration_mode === 'team'
            ? Team::where('captain_id', Auth::id())
                ->where('sport_id', $competition->sport_id)
                ->orderBy('name')
                ->get()
            : collect();

        $participants = $competition->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('registrant')
            ->latest('registered_at')
            ->get();

        return view('competitions.show', compact('competition', 'teams', 'participants'));
    }

    public function create(): View
    {
        $sports = Sport::orderBy('name')->get();

        return view('competitions.create', compact('sports'));
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['registration_mode'] !== 'team') {
            $validated['min_team_members'] = null;
            $validated['max_team_members'] = null;
        }

        Competition::create([
            'organizer_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()
            ->route('competitions.index')
            ->with('success', 'Competition created successfully.');
    }

    public function register(Request $request, Competition $competition): RedirectResponse
    {
        if ($competition->status !== 'published') {
            return back()->with('error', 'This competition is not open for registration.');
        }

        if (now()->greaterThanOrEqualTo($competition->registration_deadline)) {
            return back()->with('error', 'Registration for this competition has closed.');
        }

        $team = null;
        if ($competition->registration_mode === 'team') {
            $validated = $request->validate([
                'team_id' => ['required', 'integer', 'exists:teams,id'],
            ]);

            $team = Team::whereKey($validated['team_id'])
                ->where('captain_id', Auth::id())
                ->where('sport_id', $competition->sport_id)
                ->first();

            if (! $team) {
                return back()->with('error', 'You can only register a team you captain for this sport.');
            }

            $memberCount = $team->members()->count();

            if ($competition->min_team_members !== null && $memberCount < $competition->min_team_members) {
                return back()->with('error', "Your team needs at least {$competition->min_team_members} members to register.");
            }

            if ($competition->max_team_members !== null && $memberCount > $competition->max_team_members) {
                return back()->with('error', "Your team has too many members. This competition allows at most {$competition->max_team_members}.");
            }
        }

        $registrantType = $team ? 'team' : 'user';
        $registrantId = $team?->id ?? Auth::id();

        $result = DB::transaction(function () use ($competition, $registrantType, $registrantId): string {
            $existingRegistration = $competition->registrations()
                ->where('registrant_type', $registrantType)
                ->where('registrant_id', $registrantId)
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
            ->where('registrant_type', $registrantType)
            ->where('registrant_id', $registrantId)
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

    public function cancelRegistration(Request $request, Competition $competition): RedirectResponse
    {
        $teamId = $request->integer('team_id');
        $registration = $competition->registrations()
            ->where(function ($query) use ($teamId) {
                $query->where(function ($query) {
                    $query->where('registrant_type', 'user')
                        ->where('registrant_id', Auth::id());
                })->orWhere(function ($query) use ($teamId) {
                    $query->where('registrant_type', 'team')
                        ->whereHas('registrant', function ($query) use ($teamId) {
                            $query->where('captain_id', Auth::id())
                                ->when($teamId, fn ($query) => $query->whereKey($teamId));
                        });
                });
            })
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
