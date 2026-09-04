<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
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
            
            // Add expiration if available
            if ($user->google_token_expires_at) {
                $tokenData['expires_in'] = $user->google_token_expires_at->diffInSeconds(now());
            }
            
            $this->client->setAccessToken($tokenData);
            
            // Check if token is expired and refresh if needed
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    // Save the new token to database
                    $this->user->update([
                        'google_access_token' => $this->client->getAccessToken()['access_token'] ?? $user->google_access_token,
                        'google_token_expires_at' => now()->addSeconds($this->client->getAccessToken()['expires_in'] ?? 3600),
                    ]);
                }
            }
        }
        
        $this->calendar = new Calendar($this->client);
    }

    public function createEvent($summary, $description, $startTime, $endTime)
    {
        $timezone = config('app.timezone', 'UTC');

        $start = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $startTime, $timezone)->toIso8601String();
        $end   = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $endTime,   $timezone)->toIso8601String();

        $event = new \Google\Service\Calendar\Event([
            'summary'     => $summary,
            'description' => $description,
            'start'       => ['dateTime' => $start, 'timeZone' => $timezone],
            'end'         => ['dateTime' => $end,   'timeZone' => $timezone],
        ]);

        return $this->calendar->events->insert('primary', $event);
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

    public function getEvent(string $eventId)
    {
        return $this->calendar->events->get('primary', $eventId);
    }

    public function deleteEvent(string $eventId): void
    {
        $this->calendar->events->delete('primary', $eventId);
    }
}

