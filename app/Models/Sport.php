<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'result_type',
    ];

    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * The sort direction that ranks results best-first for this sport:
     * ascending for time (fastest wins), descending for score (highest wins).
     */
    public function sortDirection(): string
    {
        return $this->result_type === 'time' ? 'asc' : 'desc';
    }
}
