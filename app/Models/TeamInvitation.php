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

    /**
     * Move a pending invitation to the given status, atomically. Returns false when another
     * request already responded to it, so only one response can ever win.
     */
    public function respond(string $status): bool
    {
        $respondedAt = now();

        $updated = static::whereKey($this->getKey())
            ->where('status', 'pending')
            ->update(['status' => $status, 'responded_at' => $respondedAt]);

        if ($updated === 1) {
            $this->forceFill(['status' => $status, 'responded_at' => $respondedAt])->syncOriginal();
        }

        return $updated === 1;
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
