<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetitionWinnerController extends Controller
{
    public function update(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        $validated = $request->validate([
            'winner_type' => ['required', 'in:user,team'],
            'winner_id' => ['required', 'integer'],
        ]);

        $isConfirmed = $competition->registrations()
            ->where('registrant_type', $validated['winner_type'])
            ->where('registrant_id', $validated['winner_id'])
            ->where('status', 'confirmed')
            ->exists();

        if (! $isConfirmed) {
            return back()->with('error', 'The winner must be a confirmed participant in this competition.');
        }

        $competition->update([
            'winner_type' => $validated['winner_type'],
            'winner_id' => $validated['winner_id'],
        ]);

        return back()->with('success', 'Winner saved.');
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        $competition->update([
            'winner_type' => null,
            'winner_id' => null,
        ]);

        return back()->with('success', 'Winner cleared.');
    }
}
