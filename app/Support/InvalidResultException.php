<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * A competition result could not be accepted; the message is safe to show to the user.
 */
class InvalidResultException extends InvalidArgumentException
{
}
