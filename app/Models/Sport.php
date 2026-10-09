<?php

namespace App\Models;

use App\Support\ResultFormat;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'result_type',
        'result_decimals',
        'result_max',
    ];

    protected static function booted(): void
    {
        // Switching between time and score flips the ranking direction of every competition.
        static::saved(function (Sport $sport) {
            if ($sport->wasChanged('result_type')) {
                $sport->competitions()->whereHas('results')->pluck('id')
                    ->each(fn (int $competitionId) => Result::recalculatePositions($competitionId));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'result_decimals' => 'integer',
            'result_max' => 'decimal:3',
        ];
    }

    /**
     * How this sport's results are entered, validated and displayed.
     */
    public function resultFormat(): ResultFormat
    {
        return ResultFormat::for($this);
    }

    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * The sort direction that ranks results best-first for this sport:
     * ascending for time (fastest wins), descending for score (highest wins).
     */
    public function sortDirection(): string
    {
        return $this->result_type === 'time' ? 'asc' : 'desc';
    }
}
