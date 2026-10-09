<?php

use App\Support\InvalidResultException;
use App\Support\ResultFormat;

// --- Time ---

it('parses times written as seconds, m:ss and h:mm:ss into seconds', function (string $input, string $stored) {
    expect((new ResultFormat('time', 2))->parse($input))->toBe($stored);
})->with([
    'seconds' => ['12.43', '12.430'],
    'whole seconds' => ['59', '59.000'],
    'minutes' => ['1:03.48', '63.480'],
    'minutes without fraction' => ['2:05', '125.000'],
    'hours' => ['1:02:03.4', '3723.400'],
    'trailing zeros are not extra precision' => ['1:03.480', '63.480'],
    'surrounding whitespace' => [' 9.58 ', '9.580'],
]);

it('rejects malformed, negative, zero or too precise times', function (string $input, string $message) {
    expect(fn () => (new ResultFormat('time', 2))->parse($input))
        ->toThrow(InvalidResultException::class, $message);
})->with([
    'negative' => ['-12.4', 'Enter the time as seconds, m:ss or h:mm:ss'],
    'zero' => ['0', 'A time must be greater than zero.'],
    'zero with fraction' => ['0:00.00', 'A time must be greater than zero.'],
    'seconds of 60 or more after minutes' => ['1:75', 'Seconds must be two digits between 00 and 59'],
    'one digit seconds after minutes' => ['1:3', 'Seconds must be two digits between 00 and 59'],
    'minutes of 60 or more after hours' => ['1:75:00', 'Minutes must be two digits between 00 and 59'],
    'too many parts' => ['1:00:00:00', 'Enter the time as seconds, m:ss or h:mm:ss'],
    'too many decimals' => ['12.435', 'The result may have at most 2 decimals.'],
    'letters' => ['12s', 'Enter the time as seconds, m:ss or h:mm:ss'],
    'exponent' => ['1e3', 'Enter the time as seconds, m:ss or h:mm:ss'],
    'empty' => ['', 'Enter a result.'],
]);

it('formats times with the sport precision', function () {
    $hundredths = new ResultFormat('time', 2);

    expect($hundredths->format('12.430'))->toBe('12.43s')
        ->and($hundredths->format('125.500'))->toBe('2:05.50')
        ->and($hundredths->format('3723.400'))->toBe('1:02:03.40')
        ->and((new ResultFormat('time', 0))->format('7384'))->toBe('2:03:04')
        ->and((new ResultFormat('time', 3))->format('9.581'))->toBe('9.581s');
});

it('round trips a stored time through the input format', function () {
    $format = new ResultFormat('time', 2);

    expect($format->parse($format->inputValue('3723.400')))->toBe('3723.400');
});

// --- Score ---

it('accepts whole points and rejects fractions when the sport scores in whole numbers', function () {
    $points = new ResultFormat('score', 0);

    expect($points->parse('87'))->toBe('87.000')
        ->and($points->parse('0'))->toBe('0.000')
        ->and($points->parse('87.0'))->toBe('87.000')
        ->and(fn () => $points->parse('87.5'))->toThrow(InvalidResultException::class, 'The score must be a whole number.');
});

it('accepts decimal scores up to the sport precision', function () {
    $gymnastics = new ResultFormat('score', 2, '10.000');

    expect($gymnastics->parse('9.85'))->toBe('9.850')
        ->and($gymnastics->format('9.850'))->toBe('9.85 pts')
        ->and(fn () => $gymnastics->parse('9.855'))->toThrow(InvalidResultException::class, 'at most 2 decimals')
        ->and(fn () => $gymnastics->parse('10.5'))->toThrow(InvalidResultException::class, 'The result may be at most 10 pts.');
});

it('rejects negative or non numeric scores', function (string $input) {
    expect(fn () => (new ResultFormat('score', 0))->parse($input))
        ->toThrow(InvalidResultException::class, 'Enter the score as a number that is not negative');
})->with(['-5', '+5', 'abc', '1,5', '1e2']);

// --- Range ---

it('enforces the sport maximum and the storage limit', function () {
    expect(fn () => (new ResultFormat('time', 2, '7200'))->parse('2:00:00.01'))
        ->toThrow(InvalidResultException::class, 'The result may be at most 2:00:00.00.')
        ->and(fn () => (new ResultFormat('score', 0))->parse('99999999999'))
        ->toThrow(InvalidResultException::class, 'The result is too large.')
        ->and((new ResultFormat('score', 0))->parse('9999999'))->toBe('9999999.000');
});

it('refuses an unknown result type or unsupported precision', function () {
    expect(fn () => new ResultFormat('distance', 2))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new ResultFormat('time', 4))->toThrow(InvalidArgumentException::class);
});
