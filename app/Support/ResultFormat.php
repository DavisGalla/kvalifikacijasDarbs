<?php

namespace App\Support;

use App\Models\Sport;
use InvalidArgumentException;

/**
 * How a sport's competition results are entered, stored, validated and displayed.
 *
 * - time:  a duration in seconds (lower wins). Entered as seconds ("63.48"), "m:ss.cc" ("1:03.48")
 *          or "h:mm:ss.cc" ("1:02:03.4"). Must be greater than zero.
 * - score: points (higher wins). Entered as a plain non-negative number.
 *
 * Every sport defines how many decimals a result may have (0 = whole numbers, at most 3) and
 * optionally a maximum. results.value is a decimal(10,3) column holding the value in the sport's
 * base unit (seconds or points), so any value allowed here is stored exactly; arithmetic is done
 * on integer thousandths to avoid floating point rounding.
 */
final class ResultFormat
{
    public const MAX_DECIMALS = 3;

    /** Largest value results.value (decimal(10,3)) can hold, in thousandths. */
    private const MAX_STORABLE_THOUSANDTHS = 9_999_999_999;

    public function __construct(
        public readonly string $type,
        public readonly int $decimals,
        public readonly ?string $max = null,
    ) {
        if (! in_array($type, ['time', 'score'], true)) {
            throw new InvalidArgumentException("Unknown result type [{$type}].");
        }

        if ($decimals < 0 || $decimals > self::MAX_DECIMALS) {
            throw new InvalidArgumentException('Result decimals must be between 0 and '.self::MAX_DECIMALS.'.');
        }
    }

    public static function for(Sport $sport): self
    {
        $type = $sport->result_type ?? 'score';

        return new self(
            $type,
            $sport->result_decimals ?? ($type === 'time' ? 2 : 0),
            $sport->result_max !== null ? (string) $sport->result_max : null,
        );
    }

    public function isTime(): bool
    {
        return $this->type === 'time';
    }

    /**
     * Parse user input into the value to store (a decimal string in seconds or points).
     *
     * @throws InvalidResultException with a message suitable for the user
     */
    public function parse(mixed $input): string
    {
        $input = trim((string) $input);

        if ($input === '') {
            throw new InvalidResultException('Enter a result.');
        }

        $thousandths = $this->isTime() ? $this->parseTime($input) : $this->parseScore($input);

        if ($this->isTime() && $thousandths === 0) {
            throw new InvalidResultException('A time must be greater than zero.');
        }

        $max = $this->maxThousandths();

        if ($thousandths > $max) {
            throw new InvalidResultException('The result may be at most '.$this->format($this->fromThousandths($max)).'.');
        }

        return $this->fromThousandths($thousandths);
    }

    /**
     * Display a stored value, e.g. "12.43s", "2:05.50", "1:02:03.40" or "87 pts".
     */
    public function format(string|int|float $value): string
    {
        $thousandths = $this->toThousandths($value);

        if (! $this->isTime()) {
            // Show the stored value faithfully, trimming only insignificant zeros.
            $score = rtrim(rtrim($this->fromThousandths($thousandths), '0'), '.');

            return $score.' pts';
        }

        $clock = $this->clock($thousandths);

        return str_contains($clock, ':') ? $clock : $clock.'s';
    }

    /**
     * A stored value as it should appear in an input field, in a format parse() accepts.
     */
    public function inputValue(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $thousandths = $this->toThousandths($value);

        return $this->isTime()
            ? $this->clock($thousandths)
            : rtrim(rtrim($this->fromThousandths($thousandths), '0'), '.');
    }

    public function placeholder(): string
    {
        return $this->isTime()
            ? 'e.g. '.$this->clock(63_480)
            : ($this->decimals === 0 ? 'e.g. 42' : 'e.g. '.number_format(9.5, $this->decimals, '.', ''));
    }

    public function description(): string
    {
        $precision = match ($this->decimals) {
            0 => $this->isTime() ? 'whole seconds' : 'whole points',
            1 => 'up to 1 decimal',
            default => "up to {$this->decimals} decimals",
        };

        $max = $this->max !== null ? ', at most '.$this->format($this->max) : '';

        return $this->isTime()
            ? "Time as seconds, m:ss or h:mm:ss ({$precision}{$max}). Fastest wins."
            : "Points ({$precision}, not negative{$max}). Highest wins.";
    }

    private function parseScore(string $input): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $input, $matches)) {
            throw new InvalidResultException('Enter the score as a number that is not negative, e.g. 42.');
        }

        return $this->combine((int) $matches[1], $matches[2] ?? '', $this->decimals === 0 ? 'The score must be a whole number.' : null);
    }

    private function parseTime(string $input): int
    {
        $parts = explode(':', $input);

        if (count($parts) > 3) {
            throw new InvalidResultException('Enter the time as seconds, m:ss or h:mm:ss, e.g. 1:03.48.');
        }

        $secondsPart = array_pop($parts);

        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $secondsPart, $matches)) {
            throw new InvalidResultException('Enter the time as seconds, m:ss or h:mm:ss, e.g. 1:03.48.');
        }

        $seconds = (int) $matches[1];
        $fraction = $matches[2] ?? '';
        $minutes = 0;
        $hours = 0;

        if ($parts !== []) {
            // With a minutes part, seconds must be written as two digits below 60 ("1:03", not "1:3").
            if (strlen($matches[1]) !== 2 || $seconds >= 60) {
                throw new InvalidResultException('Seconds must be two digits between 00 and 59, e.g. 1:03.48.');
            }

            $minutesPart = array_pop($parts);

            if (! ctype_digit($minutesPart)) {
                throw new InvalidResultException('Enter the time as seconds, m:ss or h:mm:ss, e.g. 1:03.48.');
            }

            $minutes = (int) $minutesPart;

            if ($parts !== []) {
                $hoursPart = array_pop($parts);

                if (! ctype_digit($hoursPart) || strlen($minutesPart) !== 2 || $minutes >= 60) {
                    throw new InvalidResultException('Minutes must be two digits between 00 and 59, e.g. 1:02:03.4.');
                }

                $hours = (int) $hoursPart;
            }
        }

        return $this->combine(($hours * 60 + $minutes) * 60 + $seconds, $fraction, $this->decimals === 0 ? 'The time must be in whole seconds.' : null);
    }

    /**
     * Combine whole units and fraction digits into thousandths, enforcing the sport's decimals.
     */
    private function combine(int $whole, string $fraction, ?string $wholeOnlyMessage): int
    {
        // Trailing zeros are not extra precision: "20.50" is a 1-decimal value.
        $fraction = rtrim($fraction, '0');

        if (strlen($fraction) > $this->decimals) {
            throw new InvalidResultException($wholeOnlyMessage ?? "The result may have at most {$this->decimals} decimals.");
        }

        if ($whole > intdiv(self::MAX_STORABLE_THOUSANDTHS, 1000)) {
            throw new InvalidResultException('The result is too large.');
        }

        return $whole * 1000 + (int) str_pad($fraction, 3, '0');
    }

    private function maxThousandths(): int
    {
        return $this->max !== null
            ? min($this->toThousandths($this->max), self::MAX_STORABLE_THOUSANDTHS)
            : self::MAX_STORABLE_THOUSANDTHS;
    }

    /**
     * Thousandths as h:mm:ss.d, m:ss.d or s.d with the sport's decimals (no unit suffix).
     */
    private function clock(int $thousandths): string
    {
        $scale = 10 ** (3 - $this->decimals);
        $units = intdiv($thousandths + intdiv($scale, 2), $scale); // round to the sport's precision
        $perSecond = 10 ** $this->decimals;

        $totalSeconds = intdiv($units, $perSecond);
        $fraction = $this->decimals > 0 ? '.'.str_pad((string) ($units % $perSecond), $this->decimals, '0', STR_PAD_LEFT) : '';

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        return match (true) {
            $hours > 0 => sprintf('%d:%02d:%02d', $hours, $minutes, $seconds).$fraction,
            $minutes > 0 => sprintf('%d:%02d', $minutes, $seconds).$fraction,
            default => $seconds.$fraction,
        };
    }

    private function toThousandths(string|int|float $value): int
    {
        return (int) round(((float) $value) * 1000);
    }

    private function fromThousandths(int $thousandths): string
    {
        return intdiv($thousandths, 1000).'.'.str_pad((string) ($thousandths % 1000), 3, '0', STR_PAD_LEFT);
    }
}
