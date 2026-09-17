<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class Result extends Model
{
    protected $fillable = [
        'competition_id',
        'registrant_type',
        'registrant_id',
        'value',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:3',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'team' => Team::class,
        ]);

        static::saved(function (Result $result) {
            static::recalculatePositions($result->competition);
        });

        static::deleted(function (Result $result) {
            static::recalculatePositions($result->competition);
        });
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function registrant(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Rank a competition's results by value, best first, according to its
     * sport's sort direction. Tied values share a position ("1224" ranking).
     */
    public static function recalculatePositions(Competition $competition): void
    {
        $results = $competition->results()
            ->orderBy('value', $competition->sport->sortDirection())
            ->get();

        $position = 0;
        $previousValue = null;

        foreach ($results as $index => $result) {
            if ($previousValue === null || $result->value !== $previousValue) {
                $position = $index + 1;
            }

            if ($result->position !== $position) {
                $result->position = $position;
                $result->saveQuietly();
            }

            $previousValue = $result->value;
        }
    }

    public function formattedValue(): string
    {
        if ($this->competition?->sport?->result_type !== 'time') {
            return (string) $this->value;
        }

        $totalMilliseconds = (int) round(((float) $this->value) * 1000);
        $minutes = intdiv($totalMilliseconds, 60000);
        $seconds = intdiv($totalMilliseconds % 60000, 1000);
        $milliseconds = $totalMilliseconds % 1000;

        return sprintf('%d:%02d.%03d', $minutes, $seconds, $milliseconds);
    }
}
