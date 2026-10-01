<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeleteCompetitionCalendarEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $eventId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->google_access_token) {
            return;
        }

        try {
            (new GoogleCalendarService($user))->deleteEvent($this->eventId);
        } catch (\Throwable $exception) {
            Log::warning('Competition registration calendar event deletion failed.', [
                'user_id' => $this->userId,
                'event_id' => $this->eventId,
                'exception' => $exception,
            ]);
        }
    }
}
