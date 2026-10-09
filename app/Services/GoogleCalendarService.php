<?php

namespace App\Services;

use App\Exceptions\GoogleCalendarDisconnectedException;
use App\Models\Registration;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Exception as GoogleServiceException;
use App\Models\User;

class GoogleCalendarService
{
    private const MAX_RESULTS_PER_REQUEST = 2500;

    protected $client;
    protected $calendar;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->client = new Client();
        
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope(Calendar::CALENDAR);
        
        // Set the access token from database
        if ($user->google_access_token) {
            $tokenData = [
                'access_token' => $user->google_access_token,
                'refresh_token' => $user->google_refresh_token,
            ];
            
            // Add expiration if available (seconds remaining, measured from now)
            if ($user->google_token_expires_at) {
                $tokenData['created'] = now()->timestamp;
                $tokenData['expires_in'] = max(0, $user->google_token_expires_at->timestamp - now()->timestamp);
            }
            
            $this->client->setAccessToken($tokenData);
            
            // Check if token is expired and refresh if needed
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

                    // Google answers a revoked or expired refresh token with an error (usually
                    // invalid_grant) instead of throwing; no retry can fix that.
                    if (isset($token['error']) || empty($token['access_token'])) {
                        throw new GoogleCalendarDisconnectedException('Google rejected the refresh token: '.($token['error'] ?? 'no access token returned'));
                    }

                    // Save the new token to database
                    $this->user->update([
                        'google_access_token' => $token['access_token'],
                        'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                        'google_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                    ]);
                }
            }
        }
        
        $this->calendar = new Calendar($this->client);
    }

    public function createEvent($summary, $description, Carbon|string $startTime, Carbon|string $endTime)
    {
        $timezone = config('app.timezone', 'UTC');

        $start = $this->toCalendarDateTime($startTime, $timezone);
        $end = $this->toCalendarDateTime($endTime, $timezone);

        $event = new \Google\Service\Calendar\Event([
            'summary'     => $summary,
            'description' => $description,
            'start'       => ['dateTime' => $start, 'timeZone' => $timezone],
            'end'         => ['dateTime' => $end,   'timeZone' => $timezone],
        ]);

        return $this->calendar->events->insert('primary', $event);
    }

    /**
     * The calendar event id used for a registration. It is derived from the registration (and the
     * installation's APP_KEY, so two installations never collide), which makes creating the event
     * idempotent: a repeated job finds its own event instead of adding a duplicate, and a
     * cancellation can delete the event even if its id was never saved.
     *
     * Google requires 5-1024 characters from the base32hex alphabet (0-9, a-v).
     */
    public static function registrationEventId(Registration $registration): string
    {
        return 'sport'.substr(hash('sha256', (string) config('app.key')), 0, 12).'r'.$registration->id;
    }

    /**
     * Create the event with the given id, or bring an existing one with that id up to date
     * (including one deleted earlier, which Google keeps as cancelled and would otherwise refuse).
     */
    public function upsertEvent(string $eventId, $summary, $description, Carbon|string $startTime, Carbon|string $endTime)
    {
        $timezone = config('app.timezone', 'UTC');

        $event = new \Google\Service\Calendar\Event([
            'id'          => $eventId,
            'status'      => 'confirmed',
            'summary'     => $summary,
            'description' => $description,
            'start'       => ['dateTime' => $this->toCalendarDateTime($startTime, $timezone), 'timeZone' => $timezone],
            'end'         => ['dateTime' => $this->toCalendarDateTime($endTime, $timezone), 'timeZone' => $timezone],
        ]);

        try {
            return $this->calendar->events->insert('primary', $event);
        } catch (GoogleServiceException $exception) {
            if ($exception->getCode() !== 409) {
                throw $exception;
            }

            return $this->calendar->events->update('primary', $eventId, $event);
        }
    }

    private function toCalendarDateTime(Carbon|string $dateTime, string $timezone): string
    {
        return ($dateTime instanceof Carbon
            ? $dateTime->copy()->setTimezone($timezone)
            : Carbon::parse($dateTime, $timezone)
        )->toIso8601String();
    }

    public function listEvents(?int $maxResults = null): array
    {
        $events = [];
        $pageToken = null;

        do {
            $parameters = [
                'maxResults' => $maxResults ?? self::MAX_RESULTS_PER_REQUEST,
                'orderBy' => 'startTime',
                'singleEvents' => true,
            ];

            if ($pageToken) {
                $parameters['pageToken'] = $pageToken;
            }

            $results = $this->calendar->events->listEvents('primary', $parameters);
            $events = array_merge($events, $results->getItems());
            $pageToken = $results->getNextPageToken();
        } while ($pageToken);

        return $events;
    }

    public function upcomingEvents(int $limit = 5): array
    {
        return $this->calendar->events->listEvents('primary', [
            'maxResults' => $limit,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => now()->toRfc3339String(),
        ])->getItems();
    }

    public function getEvent(string $eventId)
    {
        return $this->calendar->events->get('primary', $eventId);
    }

    public function deleteEvent(string $eventId): void
    {
        $this->calendar->events->delete('primary', $eventId);
    }
}

