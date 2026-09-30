<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutDay extends Model
{
    public const MAX_PER_USER = 14;

    protected $fillable = ['user_id', 'name', 'exercises'];

    protected function casts(): array
    {
        return ['exercises' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
