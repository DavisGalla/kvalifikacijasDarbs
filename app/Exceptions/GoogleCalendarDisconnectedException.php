<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The stored Google credentials no longer work (revoked access or a refresh token Google rejects).
 * Retrying cannot help; the user has to connect Google Calendar again.
 */
class GoogleCalendarDisconnectedException extends RuntimeException
{
}
