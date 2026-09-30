<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonalBestRequest;
use App\Http\Requests\UpdatePersonalBestRequest;
use App\Models\PersonalBest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PersonalBestController extends Controller
{
    public function index(): View
    {
        $bests = auth()->user()
            ->personalBests()
            ->with('entries')
            ->orderBy('exercise')
            ->get();
 
        $logs = auth()->user()
            ->workoutLogs()
            ->latest('performed_on')
            ->latest('id')
            ->limit(200)
            ->get()
            ->groupBy(fn ($log) => $log->performed_on->toDateString());

        $exercises = $bests->pluck('exercise')
            ->merge($logs->flatten()->pluck('exercise'))
            ->unique()
            ->sort()
            ->values();

        $days = auth()->user()->workoutDays;

        // Most recent weights per exercise, used to prefill the workout form.
        $lastWeights = $logs->flatten()
            ->unique('exercise')
            ->mapWithKeys(fn ($log) => [$log->exercise => $log->set_weights])
            ->all();

        $exercises = $exercises->merge($days->pluck('exercises')->flatten())->unique()->sort()->values();

        return view('personalBests.index', compact('bests', 'logs', 'exercises', 'days', 'lastWeights'));
    }
 
    public function store(StorePersonalBestRequest $request): RedirectResponse
    {
        auth()->user()->personalBests()->create($request->validated());
 
        return redirect()->route('pbs.index')->with('success', 'PR added!');
    }
 
    public function destroy(PersonalBest $pb): RedirectResponse
    {
        if ($pb->user_id !== auth()->id()) {
            abort(403);
        }

        $pb->delete();

        return redirect()->route('pbs.index')->with('success', 'PR deleted.');
    }

    public function update(UpdatePersonalBestRequest $request, PersonalBest $pb): RedirectResponse
    {
        if ($pb->user_id !== auth()->id()) {
            abort(403);
        }

        $pb->update($request->validated());

        return redirect()->route('pbs.index')->with('success', 'PR updated!');
    }
}
