<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'team_id',
        'invited_user_id',
        'invited_by',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
