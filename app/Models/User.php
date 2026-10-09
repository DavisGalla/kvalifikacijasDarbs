<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\PersonalBest;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * Users with competition history are anonymized instead of deleted (soft deletes on
 * anonymized_at), so registrations, results and organized competitions keep their records.
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const DELETED_AT = 'anonymized_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'google_id',
        'avatar',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * Who may sign in to the Filament admin panel: admins everywhere, and
     * (for developer convenience) anyone while running locally.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin || app()->environment('local');
    }

    public function personalBests(): HasMany
    {
        return $this->hasMany(PersonalBest::class);
    }

    public function workoutDays(): HasMany
    {
        return $this->hasMany(WorkoutDay::class)->orderBy('id');
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function registrations(): MorphMany
    {
        return $this->morphMany(Registration::class, 'registrant');
    }

    public function results(): MorphMany
    {
        return $this->morphMany(Result::class, 'registrant');
    }

    public function officiatedCompetitions()
    {
        return $this->belongsToMany(Competition::class, 'competition_officials')
            ->withPivot('assigned_at');
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'invited_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function organizedCompetitions(): HasMany
    {
        return $this->hasMany(Competition::class, 'organizer_id');
    }

    public function captainedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'captain_id');
    }

    public function wonCompetitions(): MorphMany
    {
        return $this->morphMany(Competition::class, 'winner');
    }

    /**
     * Whether competition records refer to this user, so deleting the row would lose history
     * (or cascade-delete organized competitions and captained teams).
     */
    public function hasCompetitionHistory(): bool
    {
        return $this->registrations()->exists()
            || $this->results()->exists()
            || $this->wonCompetitions()->exists()
            || $this->organizedCompetitions()->exists()
            || $this->captainedTeams()->withTrashed()->exists()
            || $this->teamMemberships()->whereHas('team', fn ($query) => $query->onlyTrashed())->exists();
    }
}
