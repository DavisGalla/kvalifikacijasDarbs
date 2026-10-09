<?php

namespace App\Services;

use App\Jobs\CreateCompetitionCalendarEvent;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Connecting and disconnecting a user's Google Calendar.
 */
class GoogleCalendarConnection
{
    /**
     * Disconnect at the user's request: revoke the app's access at Google, then forget the tokens.
     */
    public function disconnect(User $user): void
    {
        $this->revoke($user->google_refresh_token ?? $user->google_access_token);

        $this->forget($user);
    }

    /**
     * Forget the stored tokens without contacting Google, e.g. when Google already rejected them.
     */
    public function forget(User $user): void
    {
        $user->forceFill([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ])->save();

        Cache::forget("dashboard.calendar.{$user->id}");
    }

    /**
     * Revoke a token at Google (revoking the refresh token also revokes its access tokens).
     * Best effort: returns false when Google could not be reached, after logging it.
     */
    public function revoke(?string $token): bool
    {
        if (blank($token)) {
            return true;
        }

        try {
            $client = new Client;
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));

            return (bool) $client->revokeToken($token);
        } catch (\Throwable $exception) {
            Log::warning('Revoking a Google token failed.', ['exception' => $exception]);

            return false;
        }
    }

    /**
     * After (re)connecting, add the user's upcoming registrations that are not in their calendar
     * yet, e.g. ones made while disconnected or whose sync failed because access had expired.
     */
    public function syncUpcomingRegistrations(User $user): void
    {
        Registration::query()
            ->whereIn('status', Registration::ACTIVE_STATUSES)
            ->whereNull('google_event_id')
            ->whereHas('competition', fn ($query) => $query->where('end_time', '>', now()))
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('registrant_type', 'user')->where('registrant_id', $user->id))
                ->orWhere(fn ($query) => $query
                    ->where('registrant_type', 'team')
                    ->whereHasMorph('registrant', Team::class, fn ($query) => $query->where('captain_id', $user->id))))
            ->pluck('id')
            ->each(fn (int $registrationId) => CreateCompetitionCalendarEvent::dispatch($registrationId, $user->id));
    }
}
