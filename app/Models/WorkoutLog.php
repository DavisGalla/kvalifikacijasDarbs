<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    protected $fillable = ['user_id', 'exercise', 'day_name', 'set_weights', 'performed_on'];

    protected function casts(): array
    {
        return [
            'set_weights' => 'array',
            'performed_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setCount(): int
    {
        return count($this->set_weights ?? []);
    }

    /** e.g. "2 × 80 · 1 × 85" style summary, without the unit. */
    public function weightSummary(): string
    {
        return collect($this->groupedSets())
            ->map(fn ($group) => ($group[0] > 1 ? $group[0].' × ' : '').($group[1] + 0))
            ->implode(' · ');
    }

    /**
     * Consecutive sets at the same weight collapsed for display:
     * [80, 80, 85] => [[2, 80], [1, 85]].
     *
     * @return array<int, array{0: int, 1: float}>
     */
    public function groupedSets(): array
    {
        $groups = [];

        foreach ($this->set_weights ?? [] as $weight) {
            $weight = (float) $weight;
            $last = count($groups) - 1;

            if ($last >= 0 && $groups[$last][1] === $weight) {
                $groups[$last][0]++;
            } else {
                $groups[] = [1, $weight];
            }
        }

        return $groups;
    }
}
