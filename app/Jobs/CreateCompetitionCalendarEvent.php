<?php

namespace App\Jobs;

use App\Models\Registration;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateCompetitionCalendarEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $registrationId,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $registration = Registration::find($this->registrationId);
        $user = User::find($this->userId);

        if (! $registration || ! $user || ! $user->google_access_token) {
            return;
        }

        $competition = $registration->competition;

        try {
            $event = (new GoogleCalendarService($user))->createEvent(
                $competition->title,
                $competition->description."\n\nLocation: ".$competition->location,
                $competition->start_time,
                $competition->end_time,
            );

            $registration->update(['google_event_id' => $event->getId()]);
        } catch (\Throwable $exception) {
            Log::warning('Competition registration calendar event failed.', [
                'user_id' => $this->userId,
                'registration_id' => $this->registrationId,
                'exception' => $exception,
            ]);
        }
    }
}
