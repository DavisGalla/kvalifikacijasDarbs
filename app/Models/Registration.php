<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class Registration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'competition_id',
        'registrant_type',
        'registrant_id',
        'status',
        'registered_at',
        'google_event_id',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'team' => Team::class,
        ]);
    }

    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
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
}
