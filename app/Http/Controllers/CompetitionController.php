<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Models\Competition;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::with('sport')
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        return view('competitions.index', compact('competitions'));
    }

    public function create(): View
    {
        $sports = Sport::orderBy('name')->get();

        return view('competitions.create', compact('sports'));
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        Competition::create([
            'organizer_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return redirect()
            ->route('competitions.index')
            ->with('success', 'Competition created successfully.');
    }
}
