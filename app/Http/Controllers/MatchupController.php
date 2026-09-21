<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Matchup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchupController extends Controller
{
    public function store(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);
        abort_unless($competition->registration_mode === 'team', 404);

        $validated = $request->validate([
            'home_team_id' => ['required', 'integer', 'different:away_team_id'],
            'away_team_id' => ['required', 'integer'],
            'home_score' => ['required', 'integer', 'min:0'],
            'away_score' => ['required', 'integer', 'min:0'],
        ]);

        $confirmedTeamIds = $competition->registrations()
            ->where('registrant_type', 'team')
            ->where('status', 'confirmed')
            ->pluck('registrant_id');

        if (
            ! $confirmedTeamIds->contains($validated['home_team_id'])
            || ! $confirmedTeamIds->contains($validated['away_team_id'])
        ) {
            return back()->with('error', 'Both teams must be confirmed participants in this competition.');
        }

        $competition->matchups()->create($validated);

        return back()->with('success', 'Matchup saved.');
    }

    public function destroy(Competition $competition, Matchup $matchup): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);
        abort_unless($matchup->competition_id === $competition->id, 404);

        $matchup->delete();

        return back()->with('success', 'Matchup removed.');
    }
}
