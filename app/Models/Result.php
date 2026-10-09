<?php

namespace App\Models;

use App\Services\CompetitionResults;
use App\Support\ResultFormat;
use App\Support\RowLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * A participant's result in a competition.
 *
 * Positions are never entered by hand: they are derived from the values and the sport's ranking
 * direction (fastest time or highest score first) every time a competition's results change, and
 * stored only so that lists and the admin panel can show and sort by them cheaply.
 */
class Result extends Model
{
    protected $fillable = [
        'competition_id',
        'registrant_type',
        'registrant_id',
        'value',
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

        // Final guarantee that no write path (controller, admin panel, seeder) stores a value the
        // competition's sport does not allow; throws InvalidResultException otherwise.
        static::saving(function (Result $result) {
            if ($result->isDirty('value')) {
                $result->value = $result->format()->parse($result->value);
            }
        });

        static::saved(function (Result $result) {
            static::recalculatePositions($result->competition_id);

            // A result moved to another competition (admin edit) leaves a gap in the old ranking.
            if ($result->wasChanged('competition_id') && $result->getOriginal('competition_id')) {
                static::recalculatePositions($result->getOriginal('competition_id'));
            }
        });

        static::deleted(function (Result $result) {
            static::recalculatePositions($result->competition_id);
        });
    }

    /**
     * Save under the competition's lock, so concurrent result entries for one competition are
     * serialized and the ranking computed after each write sees every result.
     */
    public function save(array $options = []): bool
    {
        if ($this->isDirty('competition_id')) {
            $this->unsetRelation('competition');
        }

        return DB::transaction(function () use ($options): bool {
            static::lockCompetitions([$this->competition_id, $this->getOriginal('competition_id')]);

            return parent::save($options);
        }, 3);
    }

    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            static::lockCompetitions([$this->competition_id]);

            return parent::delete();
        }, 3);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function registrant(): MorphTo
    {
        // Archived teams stay resolvable so historical records keep their participant.
        return $this->morphTo()->withTrashed();
    }

    /**
     * Results best first for the competition's sport, ignoring stored positions, so lists are
     * ordered correctly even before positions have been recalculated.
     */
    public function scopeRanked(Builder $query, Competition $competition): void
    {
        $query->orderBy('value', $competition->sport->sortDirection())->orderBy('id');
    }

    /**
     * Rank a competition's results by value, best first, according to its sport's sort direction.
     * Tied values share a position and the next position is skipped ("1224" ranking).
     */
    public static function recalculatePositions(Competition|int $competition): void
    {
        DB::transaction(function () use ($competition): void {
            $competitionId = $competition instanceof Competition ? $competition->id : $competition;

            $competition = static::lockCompetitions([$competitionId])[$competitionId] ?? null;

            if (! $competition) {
                return;
            }

            $results = $competition->results()->ranked($competition)->get(['id', 'value', 'position']);

            $position = 0;
            $previousValue = null;

            foreach ($results as $index => $result) {
                if ($previousValue === null || $result->value !== $previousValue) {
                    $position = $index + 1;
                }

                // A query update: no events, and no second pass through save()'s locking.
                if ($result->position !== $position) {
                    static::whereKey($result->id)->update(['position' => $position]);
                }

                $previousValue = $result->value;
            }

            app(CompetitionResults::class)->syncAutomaticWinner($competitionId);
        }, 3);
    }

    /**
     * Lock the given competitions (in id order, so concurrent callers cannot deadlock).
     *
     * @param  array<int|null>  $ids
     * @return array<int, Competition>
     */
    private static function lockCompetitions(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);

        $locked = [];

        foreach ($ids as $id) {
            if ($competition = RowLock::lock((new Competition)->forceFill(['id' => $id]))) {
                $locked[$id] = $competition;
            }
        }

        return $locked;
    }

    public function format(): ResultFormat
    {
        $sport = $this->competition?->sport ?? throw new LogicException('A result needs a competition with a sport.');

        return $sport->resultFormat();
    }

    public function formattedValue(): string
    {
        return $this->format()->format($this->value);
    }
}
