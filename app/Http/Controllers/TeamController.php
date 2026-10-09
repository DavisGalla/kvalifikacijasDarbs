<?php

namespace App\Http\Controllers;

use App\Exceptions\RosterChangeException;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\Sport;
use App\Models\User;
use App\Services\TeamRoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $teams = Team::with(['sport', 'captain'])
            ->withCount('members')
            ->where(fn ($query) => $query
                ->where('is_public', true)
                ->orWhere('captain_id', $userId)
                ->orWhereHas('members', fn ($query) => $query->where('user_id', $userId)))
            ->latest('created_at')
            ->paginate(12);

        $invitations = TeamInvitation::with(['team', 'inviter'])
            ->where('invited_user_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('teams.index', compact('teams', 'invitations'));
    }

    public function create(): View
    {
        $sports = Sport::orderBy('name')->get();

        return view('teams.create', compact('sports'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = DB::transaction(function () use ($request): Team {
            $team = Team::create([
                'name' => $request->validated('name'),
                'sport_id' => $request->validated('sport_id'),
                'captain_id' => Auth::id(),
                'is_public' => $request->boolean('is_public'),
            ]);

            $team->members()->create([
                'user_id' => Auth::id(),
                'role' => 'captain',
                'joined_at' => now(),
            ]);

            return $team;
        });

        return redirect()->route('teams.show', $team)->with('success', 'Team created successfully.');
    }

    public function show(Team $team): View
    {
        $userId = Auth::id();
        $isMember = $team->members()->where('user_id', $userId)->exists();

        abort_unless($team->is_public || $team->captain_id === $userId || $isMember, 404);

        $team->load(['sport', 'captain', 'members.user']);

        return view('teams.show', compact('team', 'isMember'));
    }

    public function join(Team $team, TeamRoster $roster): RedirectResponse
    {
        abort_unless($team->is_public, 404);

        try {
            $roster->addMember($team, Auth::user());
        } catch (RosterChangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'You joined the team.');
    }

    public function leave(Team $team, TeamRoster $roster): RedirectResponse
    {
        try {
            $roster->removeMember($team, Auth::user());
        } catch (RosterChangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('teams.index')->with('success', 'You left the team.');
    }

    public function destroy(Team $team, TeamRoster $roster): RedirectResponse
    {
        abort_unless($team->captain_id === Auth::id(), 403);

        try {
            $archived = $roster->deleteTeam($team);
        } catch (RosterChangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('teams.index')->with('success', $archived
            ? 'Team archived. Its competition history has been kept.'
            : 'Team deleted successfully.');
    }

    public function invite(Team $team): RedirectResponse
    {
        abort_unless($team->captain_id === Auth::id(), 403);

        request()->validate([
            // Anonymized (soft-deleted) accounts cannot be invited.
            'username' => ['required', 'string', Rule::exists('users', 'username')->whereNull('anonymized_at')],
        ]);

        $user = User::where('username', request('username'))->firstOrFail();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot invite yourself.');
        }

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'That user is already a member of this team.');
        }

        $invitation = TeamInvitation::where('team_id', $team->id)
            ->where('invited_user_id', $user->id)
            ->first();

        if ($invitation?->status === 'pending') {
            return back()->with('error', 'That user already has a pending invitation.');
        }

        if ($invitation) {
            $invitation->update([
                'invited_by' => Auth::id(),
                'status' => 'pending',
                'responded_at' => null,
                'created_at' => now(),
            ]);
        } else {
            TeamInvitation::create([
                'team_id' => $team->id,
                'invited_user_id' => $user->id,
                'invited_by' => Auth::id(),
                'status' => 'pending',
                'created_at' => now(),
            ]);
        }

        return back()->with('success', 'Invitation sent.');
    }

    public function acceptInvitation(TeamInvitation $invitation, TeamRoster $roster): RedirectResponse
    {
        abort_unless($invitation->invited_user_id === Auth::id(), 403);

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation is no longer pending.');
        }

        if (! $invitation->team) {
            return back()->with('error', 'This team no longer exists.');
        }

        try {
            $roster->locked($invitation->team, function (Team $team) use ($invitation, $roster): void {
                // Claim the invitation first; a second tab or a concurrent decline loses here.
                if (! $invitation->respond('accepted')) {
                    throw new RosterChangeException('This invitation is no longer pending.');
                }

                // A refused join rolls the claim back, leaving the invitation pending so it can
                // be accepted once a spot frees up.
                if (! $team->members()->where('user_id', Auth::id())->exists()) {
                    $roster->addMember($team, Auth::user());
                }
            });
        } catch (RosterChangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'You joined the team.');
    }

    public function declineInvitation(TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->invited_user_id === Auth::id(), 403);

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation is no longer pending.');
        }

        if (! $invitation->respond('declined')) {
            return back()->with('error', 'This invitation is no longer pending.');
        }

        return back()->with('success', 'Invitation declined.');
    }
}
