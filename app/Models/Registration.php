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

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function registrant(): MorphTo
    {
        return $this->morphTo();
    }
}
