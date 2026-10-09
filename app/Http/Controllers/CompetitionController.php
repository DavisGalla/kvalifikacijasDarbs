<?php

namespace App\Http\Controllers;

use App\Exceptions\RosterChangeException;
use App\Http\Requests\StoreCompetitionRequest;
use App\Jobs\CreateCompetitionCalendarEvent;
use App\Jobs\DeleteCompetitionCalendarEvent;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\Team;
use App\Services\GoogleCalendarService;
use App\Services\TeamRoster;
use App\Support\RowLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
            ->paginate(12);

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
                        ->whereHasMorph('registrant', Team::class, fn ($query) => $query->withTrashed()->where('captain_id', Auth::id()));
                });
            })
            ->with(['competition.sport'])
            ->latest('registered_at')
            ->paginate(20);

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
                            ->whereHasMorph('registrant', Team::class, fn ($query) => $query->withTrashed()->where('captain_id', Auth::id()));
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

        $results = $competition->results()
            ->with('registrant')
            ->ranked($competition)
            ->get()
            ->each->setRelation('competition', $competition);

        $matchups = $competition->registration_mode === 'team'
            ? $competition->matchups()->with('homeTeam', 'awayTeam')->latest()->get()
            : collect();

        $canManage = $competition->isManagedBy(Auth::user());

        return view('competitions.show', compact('competition', 'teams', 'participants', 'results', 'matchups', 'canManage'));
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

    public function register(Request $request, Competition $competition, TeamRoster $roster): RedirectResponse
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
        }

        $registrantType = $team ? 'team' : 'user';
        $registrantId = $team?->id ?? Auth::id();

        try {
            $result = DB::transaction(function () use ($competition, $team, $roster, $registrantType, $registrantId): string {
                // Lock the competition row itself so concurrent registrations are serialized even
                // when no registration rows exist yet, then re-check against the locked state.
                $competition = RowLock::lock($competition);

                if (! $competition || $competition->status !== 'published') {
                    return 'closed';
                }

                if (now()->greaterThanOrEqualTo($competition->registration_deadline)) {
                    return 'deadline_passed';
                }

                if ($team) {
                    // Take the team's roster lock, so no member can join or leave between this count
                    // and the commit. The lock is held until the outer transaction ends.
                    $memberCount = $roster->locked($team, fn (Team $team) => $team->members()->count());

                    if ($competition->min_team_members !== null && $memberCount < $competition->min_team_members) {
                        return 'team_too_small';
                    }

                    if ($competition->max_team_members !== null && $memberCount > $competition->max_team_members) {
                        return 'team_too_large';
                    }
                }

                $existingRegistration = $competition->registrations()
                    ->where('registrant_type', $registrantType)
                    ->where('registrant_id', $registrantId)
                    ->lockForUpdate()
                    ->first();

                if (in_array($existingRegistration?->status, ['pending', 'confirmed'], true)) {
                    return 'already_registered';
                }

                $activeRegistrations = $competition->registrations()
                    ->whereIn('status', ['pending', 'confirmed'])
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
                    'registrant_type' => $registrantType,
                    'registrant_id' => $registrantId,
                    'status' => 'pending',
                    'registered_at' => now(),
                ]);

                return 'registered';
            }, 3);
        } catch (RosterChangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result === 'closed') {
            return back()->with('error', 'This competition is not open for registration.');
        }

        if ($result === 'deadline_passed') {
            return back()->with('error', 'Registration for this competition has closed.');
        }

        if ($result === 'team_too_small') {
            return back()->with('error', "Your team needs at least {$competition->min_team_members} members to register.");
        }

        if ($result === 'team_too_large') {
            return back()->with('error', "Your team has too many members. This competition allows at most {$competition->max_team_members}.");
        }

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

        if (! $registration) {
            return back()->with('error', 'Registration could not be saved. Please try again.');
        }

        if (! Auth::user()->google_access_token) {
            return back()->with('success', 'Registration submitted successfully. Connect Google Calendar to add it to your calendar.');
        }

        CreateCompetitionCalendarEvent::dispatch($registration->id, Auth::id());

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
            ->get();

        if ($registration->isEmpty()) {
            return back()->with('error', 'You are not registered for this competition.');
        }

        if ($registration->count() > 1) {
            return back()->with('error', 'Please specify which registration to cancel.');
        }

        $registration = $registration->first();

        if (now()->greaterThanOrEqualTo($competition->start_time)) {
            return back()->with('error', 'You cannot leave a competition after it has started.');
        }

        // The event id is known even when the create job has not saved it yet, so an event created
        // around the time of cancelling is removed too (the create job also re-checks the status).
        $eventId = $registration->google_event_id ?? GoogleCalendarService::registrationEventId($registration);

        $registration->update([
            'status' => 'cancelled',
            'google_event_id' => null,
        ]);

        if (Auth::user()->google_access_token) {
            DeleteCompetitionCalendarEvent::dispatch(Auth::id(), $eventId, $registration->id);
        }

        return back()->with('success', 'You have left the competition.');
    }
}
