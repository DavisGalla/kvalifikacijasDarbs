<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $teams = Team::with(['sport', 'captain'])
            ->withCount('members')
            ->where('is_public', true)
            ->orWhere('captain_id', $userId)
            ->orWhereHas('members', fn ($query) => $query->where('user_id', $userId))
            ->latest('created_at')
            ->get();

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

    public function join(Team $team): RedirectResponse
    {
        abort_unless($team->is_public, 404);

        if ($team->members()->where('user_id', Auth::id())->exists()) {
            return back()->with('error', 'You are already a member of this team.');
        }

        $team->members()->create([
            'user_id' => Auth::id(),
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'You joined the team.');
    }

    public function leave(Team $team): RedirectResponse
    {
        $member = $team->members()
            ->where('user_id', Auth::id())
            ->where('role', 'member')
            ->first();

        if (! $member) {
            return back()->with('error', 'You cannot leave this team.');
        }

        $remaining = $team->members()->count() - 1;

        $blocking = $team->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('competition', fn ($query) => $query
                ->where('start_time', '>', now())
                ->whereNotNull('min_team_members')
                ->where('min_team_members', '>', $remaining))
            ->exists();

        if ($blocking) {
            return back()->with('error', 'Leaving would drop the team below the minimum size of a competition it is registered for.');
        }

        $member->delete();

        return redirect()->route('teams.index')->with('success', 'You left the team.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        abort_unless($team->captain_id === Auth::id(), 403);

        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted successfully.');
    }

    public function invite(Team $team): RedirectResponse
    {
        abort_unless($team->captain_id === Auth::id(), 403);

        request()->validate([
            'username' => ['required', 'string', 'exists:users,username'],
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

    public function acceptInvitation(TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->invited_user_id === Auth::id(), 403);

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation is no longer pending.');
        }

        DB::transaction(function () use ($invitation): void {
            $invitation->update(['status' => 'accepted', 'responded_at' => now()]);
            $invitation->team->members()->firstOrCreate(
                ['user_id' => Auth::id()],
                ['role' => 'member', 'joined_at' => now()]
            );
        });

        return back()->with('success', 'You joined the team.');
    }

    public function declineInvitation(TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->invited_user_id === Auth::id(), 403);

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'This invitation is no longer pending.');
        }

        $invitation->update(['status' => 'declined', 'responded_at' => now()]);

        return back()->with('success', 'Invitation declined.');
    }
}
