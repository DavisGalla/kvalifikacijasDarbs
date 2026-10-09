<?php

namespace App\Http\Controllers;

use App\Exceptions\CompetitionRuleException;
use App\Models\Competition;
use App\Models\Matchup;
use App\Services\CompetitionResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchupController extends Controller
{
    public function store(Request $request, Competition $competition, CompetitionResults $results): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);
        abort_unless($competition->registration_mode === 'team', 404);

        $validated = $request->validate([
            'round' => ['required', 'integer', 'min:1', 'max:1000'],
            'played_on' => ['nullable', 'date'],
            'home_team_id' => ['required', 'integer', 'different:away_team_id'],
            'away_team_id' => ['required', 'integer'],
            'home_score' => ['required', 'integer', 'min:0', 'max:1000'],
            'away_score' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        try {
            $results->recordMatchup($competition, $validated);
        } catch (CompetitionRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

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
