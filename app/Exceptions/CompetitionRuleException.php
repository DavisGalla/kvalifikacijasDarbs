<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A change to a competition's results, matchups or winner was refused; the message is safe to show
 * to the user.
 */
class CompetitionRuleException extends RuntimeException
{
}
