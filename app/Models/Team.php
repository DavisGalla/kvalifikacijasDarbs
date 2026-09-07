<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'sport_id',
        'captain_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
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
}
