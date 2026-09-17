<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetitionOfficialController extends Controller
{
    public function store(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->organizer_id === Auth::id(), 403);

        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $user = User::where('username', $validated['identifier'])
            ->orWhere('email', $validated['identifier'])
            ->first();

        if (! $user) {
            return back()->with('error', 'No user found with that username or email.');
        }

        if ($user->id === $competition->organizer_id) {
            return back()->with('error', 'That user already organizes this competition.');
        }

        if ($competition->officials()->whereKey($user->id)->exists()) {
            return back()->with('error', 'That user is already an official for this competition.');
        }

        $competition->officials()->attach($user->id, ['assigned_at' => now()]);

        return back()->with('success', "{$user->name} was added as an official.");
    }

    public function destroy(Competition $competition, User $user): RedirectResponse
    {
        abort_unless($competition->organizer_id === Auth::id(), 403);

        $competition->officials()->detach($user->id);

        return back()->with('success', 'Official removed.');
    }
}
