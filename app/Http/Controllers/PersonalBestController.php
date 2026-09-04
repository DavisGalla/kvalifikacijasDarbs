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
            ->orderBy('exercise')
            ->get();
 
        return view('personalBests.index', compact('bests'));
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
