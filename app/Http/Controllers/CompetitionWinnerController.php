<?php

namespace App\Http\Controllers;

use App\Exceptions\CompetitionRuleException;
use App\Models\Competition;
use App\Services\CompetitionResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetitionWinnerController extends Controller
{
    public function update(Request $request, Competition $competition, CompetitionResults $results): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        $validated = $request->validate([
            'method' => ['required', 'in:automatic,manual'],
            'winner_type' => ['required_if:method,manual', 'nullable', 'in:user,team'],
            'winner_id' => ['required_if:method,manual', 'nullable', 'integer'],
            'winner_note' => ['required_if:method,manual', 'nullable', 'string', 'min:10', 'max:1000'],
        ], [
            'winner_note.required_if' => 'Give the reason for choosing the winner manually.',
        ]);

        try {
            if ($validated['method'] === 'automatic') {
                $results->declareAutomaticWinner($competition);
            } else {
                $results->declareManualWinner($competition, $validated['winner_type'], (int) $validated['winner_id'], $validated['winner_note']);
            }
        } catch (CompetitionRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Winner saved.');
    }

    public function destroy(Competition $competition, CompetitionResults $results): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        $results->clearWinner($competition);

        return back()->with('success', 'Winner cleared.');
    }
}
