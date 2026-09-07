<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'status',
    ];

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

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }
}
