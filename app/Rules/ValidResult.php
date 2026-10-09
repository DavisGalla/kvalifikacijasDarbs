<?php

namespace App\Rules;

use App\Support\InvalidResultException;
use App\Support\ResultFormat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a competition result against its sport's result format (type, decimals, range).
 */
class ValidResult implements ValidationRule
{
    public function __construct(private ResultFormat $format)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value)) {
            $fail('Enter a result.');

            return;
        }

        try {
            $this->format->parse($value);
        } catch (InvalidResultException $e) {
            $fail($e->getMessage());
        }
    }
}
