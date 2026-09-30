<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutLogRequest;
use App\Http\Requests\StoreWorkoutSessionRequest;
use App\Models\WorkoutLog;
use Illuminate\Http\RedirectResponse;

class WorkoutLogController extends Controller
{
    public function store(StoreWorkoutLogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['set_weights'] = array_map('floatval', array_values($data['set_weights']));
        $data['performed_on'] ??= now()->toDateString();

        auth()->user()->workoutLogs()->create($data);

        return redirect(route('pbs.index').'#workout')->with('success', 'Workout logged!');
    }

    /**
     * Log a whole program day at once: one entry per exercise that has at
     * least one weight filled in. Exercises left blank are skipped.
     */
    public function storeSession(StoreWorkoutSessionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $date = $data['performed_on'] ?? now()->toDateString();
        $logged = 0;

        foreach ($data['exercises'] as $exercise) {
            $weights = collect($exercise['set_weights'] ?? [])
                ->filter(fn ($weight) => $weight !== null && $weight !== '')
                ->map(fn ($weight) => (float) $weight)
                ->values()
                ->all();

            if ($weights === []) {
                continue;
            }

            auth()->user()->workoutLogs()->create([
                'exercise' => $exercise['name'],
                'day_name' => $data['day_name'] ?? null,
                'set_weights' => $weights,
                'performed_on' => $date,
            ]);
            $logged++;
        }

        if ($logged === 0) {
            return redirect(route('pbs.index').'#workout')
                ->withInput()
                ->with('error', 'Enter at least one weight to log this workout.');
        }

        return redirect(route('pbs.index').'#workout')
            ->with('success', "Workout logged ({$logged} ".str()->plural('exercise', $logged).').');
    }

    public function destroy(WorkoutLog $log): RedirectResponse
    {
        abort_unless($log->user_id === auth()->id(), 403);

        $log->delete();

        return redirect(route('pbs.index').'#workout')->with('success', 'Entry deleted.');
    }
}
