<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A team roster change was refused; the message is safe to show to the user.
 */
class RosterChangeException extends RuntimeException
{
}
