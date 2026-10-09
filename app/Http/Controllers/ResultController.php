<?php

namespace App\Http\Controllers;

use App\Exceptions\CompetitionRuleException;
use App\Models\Competition;
use App\Models\Result;
use App\Rules\ValidResult;
use App\Services\CompetitionResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Competition $competition, CompetitionResults $competitionResults): View
    {
        abort_unless($competition->status === 'published' || $competition->isManagedBy(Auth::user()), 404);

        $competition->load('sport', 'organizer', 'officials', 'winner');

        $registrations = $competition->registrations()
            ->where('status', 'confirmed')
            ->with('registrant')
            ->get();

        $results = $competition->results()
            ->with('registrant')
            ->ranked($competition)
            ->get()
            ->keyBy(fn (Result $result) => "{$result->registrant_type}:{$result->registrant_id}");

        $matchups = $competition->registration_mode === 'team'
            ? $competition->matchups()->with('homeTeam', 'awayTeam')->orderByDesc('round')->latest('id')->get()
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

        $standings = $competition->registration_mode === 'team' && $matchups->isNotEmpty()
            ? $competitionResults->standings($competition)
            : collect();

        $leader = $competitionResults->leader($competition);
        $leaderName = $leader
            ? $winnerOptions->first(fn ($option) => $option['type'] === $leader['type'] && (int) $option['id'] === $leader['id'])['name'] ?? null
            : null;

        $nextRound = ($matchups->max('round') ?? 0) ?: 1;

        $canManage = $competition->isManagedBy(Auth::user());

        return view('competitions.results', compact('competition', 'registrations', 'results', 'matchups', 'standings', 'confirmedTeams', 'winnerOptions', 'leaderName', 'nextRound', 'canManage'));
    }

    public function store(Request $request, Competition $competition, CompetitionResults $competitionResults): RedirectResponse
    {
        abort_unless($competition->isManagedBy(Auth::user()), 403);

        try {
            $competitionResults->assertAcceptsResults($competition);
        } catch (CompetitionRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        $format = $competition->sport->resultFormat();

        $validated = $request->validate([
            'registrant_type' => ['required', 'in:user,team'],
            'registrant_id' => ['required', 'integer'],
            'value' => ['required', new ValidResult($format)],
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
            ['value' => $format->parse($validated['value'])],
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
