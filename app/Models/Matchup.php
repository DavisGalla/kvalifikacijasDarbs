<?php

namespace App\Models;

use App\Services\CompetitionResults;
use Illuminate\Database\Eloquent\Model;

class Matchup extends Model
{
    protected $fillable = [
        'competition_id',
        'round',
        'played_on',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'played_on' => 'date',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // A winner taken from the standings follows every change to the games behind them.
        static::saved(fn (Matchup $matchup) => app(CompetitionResults::class)->syncAutomaticWinner($matchup->competition_id));
        static::deleted(fn (Matchup $matchup) => app(CompetitionResults::class)->syncAutomaticWinner($matchup->competition_id));
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id')->withTrashed();
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id')->withTrashed();
    }
}
