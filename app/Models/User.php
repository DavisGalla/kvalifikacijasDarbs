<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\PersonalBest;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function personalBests(): HasMany
    {
        return $this->hasMany(PersonalBest::class);
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
}
