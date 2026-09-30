<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutDayRequest;
use App\Models\WorkoutDay;
use Illuminate\Http\RedirectResponse;

class WorkoutDayController extends Controller
{
    public function store(StoreWorkoutDayRequest $request): RedirectResponse
    {
        if (auth()->user()->workoutDays()->count() >= WorkoutDay::MAX_PER_USER) {
            return $this->back()->with('error', 'You can have up to '.WorkoutDay::MAX_PER_USER.' program days.');
        }

        auth()->user()->workoutDays()->create($request->validated());

        return $this->back()->with('success', 'Program day saved.');
    }

    public function update(StoreWorkoutDayRequest $request, WorkoutDay $day): RedirectResponse
    {
        abort_unless($day->user_id === auth()->id(), 403);

        $day->update($request->validated());

        return $this->back()->with('success', 'Program day updated.');
    }

    public function destroy(WorkoutDay $day): RedirectResponse
    {
        abort_unless($day->user_id === auth()->id(), 403);

        $day->delete();

        return $this->back()->with('success', 'Program day deleted.');
    }

    private function back(): RedirectResponse
    {
        return redirect(route('pbs.index').'#workout');
    }
}
