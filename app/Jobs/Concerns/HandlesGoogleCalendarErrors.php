<?php

namespace App\Jobs\Concerns;

use App\Exceptions\GoogleCalendarDisconnectedException;
use App\Models\User;
use App\Services\GoogleCalendarConnection;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sorts Google Calendar failures in queued jobs into three cases:
 *
 * - the user's access is gone (revoked, refresh token rejected, 401): retrying cannot help, so the
 *   tokens are forgotten and the dashboard asks the user to reconnect; reconnecting re-syncs their
 *   upcoming registrations;
 * - temporary problems (network errors, rate limits, Google 5xx): rethrown, so the queue retries
 *   the job with backoff and records it as failed once the attempts run out;
 * - any other error from Google (bad request, missing calendar): failed at once without retries.
 */
trait HandlesGoogleCalendarErrors
{
    public int $tries = 5;

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    protected function handleGoogleError(Throwable $exception, User $user): void
    {
        if ($this->requiresReconnect($exception)) {
            Log::info('Google Calendar access is no longer valid; the user must reconnect.', [
                'user_id' => $user->id,
                'job' => static::class,
            ]);

            app(GoogleCalendarConnection::class)->forget($user);

            return;
        }

        if ($this->isTemporary($exception)) {
            throw $exception;
        }

        $this->fail($exception);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Google Calendar job failed.', [
            'job' => static::class,
            'user_id' => $this->userId,
            'exception' => $exception,
        ]);
    }

    private function requiresReconnect(Throwable $exception): bool
    {
        return $exception instanceof GoogleCalendarDisconnectedException
            || ($exception instanceof GoogleServiceException && $exception->getCode() === 401);
    }

    private function isTemporary(Throwable $exception): bool
    {
        if (! $exception instanceof GoogleServiceException) {
            // Connection failures, timeouts and other transport errors.
            return true;
        }

        $reasons = array_column($exception->getErrors() ?? [], 'reason');

        return $exception->getCode() === 429
            || $exception->getCode() >= 500
            || ($exception->getCode() === 403 && array_intersect($reasons, ['rateLimitExceeded', 'userRateLimitExceeded']) !== []);
    }
}
