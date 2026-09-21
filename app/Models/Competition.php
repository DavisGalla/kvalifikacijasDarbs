<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class Competition extends Model
{
    protected $fillable = [
        'organizer_id',
        'sport_id',
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'registration_deadline',
        'max_participants',
        'registration_mode',
        'winner_type',
        'winner_id',
        'status',
    ];

    protected static function booted(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'team' => Team::class,
        ]);
    }

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'registration_deadline' => 'datetime',
            'max_participants' => 'integer',
        ];
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function matchups()
    {
        return $this->hasMany(Matchup::class);
    }

    public function winner(): MorphTo
    {
        return $this->morphTo();
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function officials()
    {
        return $this->belongsToMany(User::class, 'competition_officials')
            ->withPivot('assigned_at');
    }

    /**
     * Whether the given user may enter or edit results for this competition:
     * the organizer, or a user the organizer has assigned as an official.
     */
    public function isManagedBy(User $user): bool
    {
        return $this->organizer_id === $user->id
            || $this->officials()->whereKey($user->id)->exists();
    }
}
