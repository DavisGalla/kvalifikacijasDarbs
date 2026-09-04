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
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar'])
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

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'                      => $googleUser->getName(),
                'google_id'                 => $googleUser->getId(),
                'avatar'                    => $googleUser->getAvatar(),
                'google_access_token'       => $googleUser->token,
                'google_refresh_token'      => $googleUser->refreshToken,
                'google_token_expires_at'   => now()->addSeconds($googleUser->expiresIn),
                'password'                  => bcrypt(str()->random(24)),
            ]
        );

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}