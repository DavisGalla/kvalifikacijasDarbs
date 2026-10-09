<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesGoogleCalendarErrors;
use App\Models\Registration;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Support\RowLock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Adds a competition registration to the user's Google Calendar.
 *
 * Safe to run more than once: the event id is derived from the registration, so a retried or
 * duplicated job updates the same event instead of creating another one. The registration is
 * checked before calling Google and again, under a lock, afterwards, so a registration cancelled
 * while the job waited or ran never keeps a calendar event.
 */
class CreateCompetitionCalendarEvent implements ShouldQueue
{
    use HandlesGoogleCalendarErrors, Queueable;

    public function __construct(
        public int $registrationId,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $registration = Registration::with('competition')->find($this->registrationId);
        $user = User::find($this->userId);

        if (! $registration || ! $registration->isActive() || ! $user || ! $user->google_access_token) {
            return;
        }

        $competition = $registration->competition;
        $eventId = $registration->google_event_id ?? GoogleCalendarService::registrationEventId($registration);

        try {
            $calendar = app(GoogleCalendarService::class, ['user' => $user]);

            $calendar->upsertEvent(
                $eventId,
                $competition->title,
                $competition->description."\n\nLocation: ".$competition->location,
                $competition->start_time,
                $competition->end_time,
            );
        } catch (Throwable $exception) {
            $this->handleGoogleError($exception, $user);

            return;
        }

        $stillActive = DB::transaction(function () use ($registration, $eventId): bool {
            $registration = RowLock::lock($registration);

            if (! $registration?->isActive()) {
                return false;
            }

            $registration->update(['google_event_id' => $eventId]);

            return true;
        });

        if (! $stillActive) {
            // Cancelled while the event was being created: take it out of the calendar again.
            DeleteCompetitionCalendarEvent::dispatch($this->userId, $eventId, $this->registrationId);
        }
    }
}
