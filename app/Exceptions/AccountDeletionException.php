<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An account could not be deleted yet; the message is safe to show to the user.
 */
class AccountDeletionException extends RuntimeException
{
}
