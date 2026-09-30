<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
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
            ->scopes(['https://www.googleapis.com/auth/calendar'])
            // Without these, Google never issues a refresh token, so once the
            // short-lived access token expires the calendar integration breaks
            // permanently with no way to recover except manually reconnecting.
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
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

        if ($connectingCalendar) {
            $user->fill([
                'google_access_token'     => $googleUser->token,
                // Google only returns a refresh token on first consent; keep the stored one otherwise.
                'google_refresh_token'    => $googleUser->refreshToken ?? $user->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn),
            ]);
        }

        $user->save();

        Auth::login($user);

        return redirect()->route($connectingCalendar ? 'calendar.index' : 'dashboard');
    }
}