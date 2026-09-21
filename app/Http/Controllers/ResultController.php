<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Competition $competition): View
    {
        abort_unless($competition->status === 'published' || $competition->isManagedBy(Auth::user()), 404);

        $competition->load('sport', 'organizer', 'officials', 'winner');

        $registrations = $competition->registrations()
            ->where('status', 'confirmed')
            ->with('registrant')
            ->get();

        $results = $competition->results()
            ->with('registrant')
            ->orderByRaw('position IS NULL, position')
            ->get()
            ->keyBy(fn (Result $result) => "{$result->registrant_type}:{$result->registrant_id}");

        $matchups = $competition->registration_mode === 'team'
            ? $competition->matchups()->with('homeTeam', 'awayTeam')->latest()->get()
            : collect();

        $confirmedTeams = $competition->registration_mode === 'team'
            ? $registrations->pluck('registrant')->filter()
            : collect();

        $winnerOptions = $registrations
            ->filter(fn ($registration) => $registration->registrant !== null)
            ->map(fn ($registration) => [
                'type' => $registration->registrant_type,
                'id' => $registration->registrant_id,
                'name' => $registration->registrant->name,
            ]);

        $canManage = $competition->isManagedBy(Auth::user());

        return view('competitions.results', compact('competition', 'registrations', 'results', 'matchups', 'confirmedTeams', 'winnerOptions', 'canManage'));
    }

    public function store(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        $validated = $request->validate([
            'registrant_type' => ['required', 'in:user,team'],
            'registrant_id' => ['required', 'integer'],
            'value' => ['required', 'numeric'],
        ]);

        $isConfirmed = $competition->registrations()
            ->where('registrant_type', $validated['registrant_type'])
            ->where('registrant_id', $validated['registrant_id'])
            ->where('status', 'confirmed')
            ->exists();

        if (! $isConfirmed) {
            return back()->with('error', 'That participant is not confirmed for this competition.');
        }

        Result::updateOrCreate(
            [
                'competition_id' => $competition->id,
                'registrant_type' => $validated['registrant_type'],
                'registrant_id' => $validated['registrant_id'],
            ],
            ['value' => $validated['value']],
        );

        return back()->with('success', 'Result saved.');
    }

    public function destroy(Competition $competition, Result $result): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);
        abort_unless($result->competition_id === $competition->id, 404);

        $result->delete();

        return back()->with('success', 'Result removed.');
    }
}
