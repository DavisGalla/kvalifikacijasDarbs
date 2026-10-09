<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleCalendarConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    private const CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar';

    public function redirectToGoogle()
    {
        // Basic sign-in only (openid/profile/email). These are non-sensitive scopes,
        // so Google does not show the "unverified app" warning.
        session()->forget('google_calendar_connect');

        return Socialite::driver('google')->redirect();
    }

    public function redirectToGoogleCalendar()
    {
        // The calendar scope is requested separately, only when the user opts in.
        session(['google_calendar_connect' => true]);

        return Socialite::driver('google')
            ->scopes([self::CALENDAR_SCOPE])
            // Without these, Google never issues a refresh token, so once the
            // short-lived access token expires the calendar integration breaks
            // permanently with no way to recover except manually reconnecting.
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request, GoogleCalendarConnection $calendarConnection)
    {
        // The user cancelled on Google's consent screen or refused the requested access.
        if ($request->filled('error')) {
            $connectingCalendar = session()->pull('google_calendar_connect', false);

            return Auth::check() && $connectingCalendar
                ? redirect()->route('calendar.connect')->with('error', 'Google Calendar was not connected. Everything else keeps working without it.')
                : redirect('/')->with('error', 'Google sign-in was cancelled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            if (app()->environment('local')) {
                Log::warning('Google OAuth state mismatch in local environment, retrying stateless.', [
                    'message' => $exception->getMessage(),
                ]);
                $googleUser = Socialite::driver('google')->stateless()->user();
            } else {
                throw $exception;
            }
        }

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);

        if (! $user->exists) {
            $user->password = bcrypt(str()->random(24));
        }

        $user->fill([
            'name'      => $googleUser->getName(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
        ]);

        // Only store tokens from the calendar flow; a basic sign-in token has no
        // calendar scope and would overwrite a working calendar token.
        $connectingCalendar = session()->pull('google_calendar_connect', false);

        // With granular consent the user can sign in but untick calendar access.
        $calendarGranted = in_array(self::CALENDAR_SCOPE, $googleUser->approvedScopes ?? [], true);

        if ($connectingCalendar && $calendarGranted) {
            $user->fill([
                'google_access_token'     => $googleUser->token,
                // Google only returns a refresh token on first consent; keep the stored one otherwise.
                'google_refresh_token'    => $googleUser->refreshToken ?? $user->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn),
            ]);
        }

        $user->save();

        Auth::login($user);

        if ($connectingCalendar && ! $calendarGranted) {
            return redirect()->route('calendar.connect')
                ->with('error', 'Calendar access was not granted, so Google Calendar is not connected.');
        }

        if ($connectingCalendar) {
            $calendarConnection->syncUpcomingRegistrations($user);

            return redirect()->route('calendar.index')->with('success', 'Google Calendar connected.');
        }

        return redirect()->route('dashboard');
    }
}