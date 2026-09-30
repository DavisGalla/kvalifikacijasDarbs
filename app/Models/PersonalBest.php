<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalBest extends Model
{
    protected $fillable = [
        'user_id',
        'exercise',
        'weight'
    ];

    protected static function booted(): void
    {
        // Every weight set (on create or edit) is kept so progress can be charted.
        static::created(fn (PersonalBest $best) => $best->entries()->create(['weight' => $best->weight]));

        static::updated(function (PersonalBest $best) {
            if ($best->wasChanged('weight')) {
                $best->entries()->create(['weight' => $best->weight]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PersonalBestEntry::class)->orderBy('id');
    }

    /**
     * Change versus the previous entry: positive, negative, or null when there
     * is nothing to compare against. Needs the entries relation loaded.
     */
    public function trend(): ?float
    {
        if ($this->entries->count() < 2) {
            return null;
        }

        $latest = $this->entries->last();
        $previous = $this->entries[$this->entries->count() - 2];

        return (float) $latest->weight - (float) $previous->weight;
    }
}
