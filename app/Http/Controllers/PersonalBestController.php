<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonalBestRequest;
use App\Http\Requests\UpdatePersonalBestRequest;
use App\Models\PersonalBest;
use App\Models\WorkoutLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PersonalBestController extends Controller
{
    private const HISTORY_DAYS_PER_PAGE = 14;

    public function index(): View
    {
        $bests = auth()->user()
            ->personalBests()
            ->with('entries')
            ->orderBy('exercise')
            ->get();
 
        // History is paged by training day, so a day's entries are never split across pages.
        $logDays = auth()->user()
            ->workoutLogs()
            ->toBase()
            ->select('performed_on')
            ->distinct()
            ->orderByDesc('performed_on')
            ->paginate(self::HISTORY_DAYS_PER_PAGE, ['performed_on'], 'history_page')
            ->fragment('workout');

        $logs = auth()->user()
            ->workoutLogs()
            ->whereIn('performed_on', $logDays->pluck('performed_on'))
            ->latest('performed_on')
            ->latest('id')
            ->get()
            ->groupBy(fn ($log) => $log->performed_on->toDateString());

        // Every exercise ever logged, not just those on the current page of history.
        $exercises = $bests->pluck('exercise')
            ->merge(auth()->user()->workoutLogs()->distinct()->pluck('exercise'))
            ->unique()
            ->sort()
            ->values();

        $days = auth()->user()->workoutDays;

        // Most recently logged weights per exercise, used to prefill the workout form.
        $lastWeights = auth()->user()
            ->workoutLogs()
            ->whereIn('id', WorkoutLog::selectRaw('max(id)')->where('user_id', auth()->id())->groupBy('exercise'))
            ->get(['exercise', 'set_weights'])
            ->mapWithKeys(fn ($log) => [$log->exercise => $log->set_weights])
            ->all();

        $exercises = $exercises->merge($days->pluck('exercises')->flatten())->unique()->sort()->values();

        return view('personalBests.index', compact('bests', 'logs', 'logDays', 'exercises', 'days', 'lastWeights'));
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
