<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Teams that have taken part in competitions are archived instead of deleted (soft deletes on
 * archived_at), so registrations, results, matchups and winners keep pointing at a real team.
 */
class Team extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    public const DELETED_AT = 'archived_at';

    protected $fillable = [
        'name',
        'sport_id',
        'captain_id',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'archived_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id')->withTrashed();
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function registrations()
    {
        return $this->morphMany(Registration::class, 'registrant');
    }

    /**
     * Active registrations for competitions that have not started yet, whose roster rules still apply.
     */
    public function upcomingRegistrations()
    {
        return $this->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('competition', fn ($query) => $query->where('start_time', '>', now()));
    }

    public function results()
    {
        return $this->morphMany(Result::class, 'registrant');
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function wonCompetitions()
    {
        return $this->morphMany(Competition::class, 'winner');
    }

    /**
     * Whether any competition record refers to this team, so deleting it would lose history.
     */
    public function hasCompetitionHistory(): bool
    {
        return $this->registrations()->exists()
            || $this->results()->exists()
            || $this->wonCompetitions()->exists()
            || Matchup::where('home_team_id', $this->id)->orWhere('away_team_id', $this->id)->exists();
    }
}
