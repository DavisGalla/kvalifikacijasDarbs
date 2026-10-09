<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesGoogleCalendarErrors;
use App\Models\Registration;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Removes a cancelled registration's event from the user's Google Calendar. An event that is
 * already gone counts as removed, so the job is safe to repeat.
 */
class DeleteCompetitionCalendarEvent implements ShouldQueue
{
    use HandlesGoogleCalendarErrors, Queueable;

    public function __construct(
        public int $userId,
        public string $eventId,
        public ?int $registrationId = null,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->google_access_token) {
            return;
        }

        // Registered again before this job ran: the event belongs to the active registration now.
        if ($this->registrationId && Registration::find($this->registrationId)?->isActive()) {
            return;
        }

        try {
            app(GoogleCalendarService::class, ['user' => $user])->deleteEvent($this->eventId);
        } catch (Throwable $exception) {
            if ($exception instanceof GoogleServiceException && in_array($exception->getCode(), [404, 410], true)) {
                return;
            }

            $this->handleGoogleError($exception, $user);
        }
    }
}
