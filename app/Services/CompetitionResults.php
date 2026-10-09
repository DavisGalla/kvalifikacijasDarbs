<?php

namespace App\Services;

use App\Exceptions\CompetitionRuleException;
use App\Models\Competition;
use App\Models\Matchup;
use App\Models\Result;
use App\Models\Team;
use App\Support\RowLock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The rules connecting a competition's results, matchups and winner, shared by the organizer pages
 * and the admin panel.
 *
 * The ranking that decides a competition:
 * - individual competitions: results, ranked by the sport's result format (positions on results);
 * - team competitions: the matchup standings (3 points per win, 1 per draw, then goal difference
 *   and goals scored) once any matchup is recorded, otherwise results like individual events.
 *
 * The winner is either 'automatic' — the ranking leader, kept in sync whenever results or matchups
 * change, and empty while the ranking has no single leader (no entries or a tie for first) — or
 * 'manual': a confirmed participant chosen by the organizer, always stored with the reason, so the
 * page can show that it was not derived from the results.
 */
class CompetitionResults
{
    public const POINTS_FOR_WIN = 3;

    public const POINTS_FOR_DRAW = 1;

    /**
     * Record a game between two confirmed teams. Within a round each team plays at most once, so a
     * resubmitted form cannot store the same game twice.
     *
     * @param  array{round: int, home_team_id: int, away_team_id: int, home_score: int, away_score: int, played_on?: ?string}  $data
     */
    public function recordMatchup(Competition $competition, array $data): Matchup
    {
        return DB::transaction(function () use ($competition, $data): Matchup {
            $competition = $this->lock($competition);

            $this->assertAcceptsResults($competition);

            if ($competition->registration_mode !== 'team') {
                throw new CompetitionRuleException('Matchups can only be recorded for team competitions.');
            }

            if ((int) $data['home_team_id'] === (int) $data['away_team_id']) {
                throw new CompetitionRuleException('A team cannot play against itself.');
            }

            foreach ([$data['home_team_id'], $data['away_team_id']] as $teamId) {
                if (! $this->isConfirmed($competition, 'team', (int) $teamId)) {
                    throw new CompetitionRuleException('Both teams must be confirmed participants in this competition.');
                }
            }

            $alreadyPlaying = $competition->matchups()
                ->where('round', $data['round'])
                ->where(fn ($query) => $query
                    ->whereIn('home_team_id', [$data['home_team_id'], $data['away_team_id']])
                    ->orWhereIn('away_team_id', [$data['home_team_id'], $data['away_team_id']]))
                ->exists();

            if ($alreadyPlaying) {
                throw new CompetitionRuleException("One of these teams already has a game in round {$data['round']}. Use another round for a rematch.");
            }

            return $competition->matchups()->create($data);
        }, 3);
    }

    /**
     * Declare the ranking leader as the winner and keep following the ranking from now on.
     */
    public function declareAutomaticWinner(Competition $competition): void
    {
        DB::transaction(function () use ($competition): void {
            $competition = $this->lock($competition);

            $this->assertCanDecideWinner($competition);

            $leader = $this->leader($competition);

            if (! $leader) {
                throw new CompetitionRuleException('The results do not decide a single winner (nothing is recorded yet, or first place is tied). Choose the winner manually and give the reason.');
            }

            $competition->update([
                'winner_type' => $leader['type'],
                'winner_id' => $leader['id'],
                'winner_method' => 'automatic',
                'winner_note' => null,
            ]);
        }, 3);
    }

    /**
     * Record a winner chosen by the organizer instead of the ranking, e.g. after a disqualification
     * or for an event decided by judges. The reason is required and shown with the winner.
     */
    public function declareManualWinner(Competition $competition, string $type, int $id, string $reason): void
    {
        DB::transaction(function () use ($competition, $type, $id, $reason): void {
            $competition = $this->lock($competition);

            $this->assertCanDecideWinner($competition);

            if (! $this->isConfirmed($competition, $type, $id)) {
                throw new CompetitionRuleException('The winner must be a confirmed participant in this competition.');
            }

            if (trim($reason) === '') {
                throw new CompetitionRuleException('Give the reason for choosing the winner manually.');
            }

            $competition->update([
                'winner_type' => $type,
                'winner_id' => $id,
                'winner_method' => 'manual',
                'winner_note' => trim($reason),
            ]);
        }, 3);
    }

    public function clearWinner(Competition $competition): void
    {
        $competition->update([
            'winner_type' => null,
            'winner_id' => null,
            'winner_method' => null,
            'winner_note' => null,
        ]);
    }

    /**
     * Re-derive an automatic winner after the ranking changed. Manual winners are left alone.
     */
    public function syncAutomaticWinner(int $competitionId): void
    {
        DB::transaction(function () use ($competitionId): void {
            $competition = RowLock::lock((new Competition)->forceFill(['id' => $competitionId]));

            if (! $competition || $competition->winner_method !== 'automatic') {
                return;
            }

            $leader = $this->leader($competition);

            $winnerId = $competition->winner_id !== null ? (int) $competition->winner_id : null;

            if ($competition->winner_type !== ($leader['type'] ?? null) || $winnerId !== ($leader['id'] ?? null)) {
                // Without a single leader the winner stays undecided until the tie is resolved.
                $competition->update([
                    'winner_type' => $leader['type'] ?? null,
                    'winner_id' => $leader['id'] ?? null,
                ]);
            }
        }, 3);
    }

    /**
     * The participant the ranking puts in sole first place, if any.
     *
     * @return array{type: string, id: int}|null
     */
    public function leader(Competition $competition): ?array
    {
        if ($competition->registration_mode === 'team' && $competition->matchups()->exists()) {
            $standings = $this->standings($competition)->values();

            if ($standings->isEmpty() || ($standings->count() > 1 && $this->standingKey($standings[0]) === $this->standingKey($standings[1]))) {
                return null;
            }

            return ['type' => 'team', 'id' => $standings[0]->team_id];
        }

        $top = $competition->results()->ranked($competition)->limit(2)->get(['id', 'registrant_type', 'registrant_id', 'value']);

        if ($top->isEmpty() || ($top->count() > 1 && $top[0]->value === $top[1]->value)) {
            return null;
        }

        return ['type' => $top[0]->registrant_type, 'id' => (int) $top[0]->registrant_id];
    }

    /**
     * The league table of a team competition: every confirmed team and every team with a recorded
     * game, best first.
     *
     * @return Collection<int, object{team_id: int, team: ?Team, played: int, won: int, drawn: int, lost: int, goals_for: int, goals_against: int, goal_difference: int, points: int}>
     */
    public function standings(Competition $competition): Collection
    {
        $matchups = $competition->matchups()->get();

        $teamIds = $competition->registrations()
            ->where('registrant_type', 'team')
            ->where('status', 'confirmed')
            ->pluck('registrant_id')
            ->merge($matchups->pluck('home_team_id'))
            ->merge($matchups->pluck('away_team_id'))
            ->map(fn ($id) => (int) $id)
            ->unique();

        $teams = Team::withTrashed()->whereIn('id', $teamIds)->get()->keyBy('id');

        $rows = $teamIds->mapWithKeys(fn (int $id) => [$id => (object) [
            'team_id' => $id,
            'team' => $teams->get($id),
            'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0,
        ]]);

        foreach ($matchups as $matchup) {
            $this->addGame($rows[$matchup->home_team_id], $matchup->home_score, $matchup->away_score);
            $this->addGame($rows[$matchup->away_team_id], $matchup->away_score, $matchup->home_score);
        }

        return $rows->sort(function (object $a, object $b): int {
            return $this->standingKey($b) <=> $this->standingKey($a)
                ?: strcasecmp($a->team?->name ?? '', $b->team?->name ?? '');
        })->values();
    }

    public function assertAcceptsResults(Competition $competition): void
    {
        if ($competition->status !== 'published') {
            throw new CompetitionRuleException('Results can only be recorded for a published competition.');
        }

        if (! $competition->hasStarted()) {
            throw new CompetitionRuleException('Results can be recorded once the competition has started ('.$competition->start_time->format('M j, Y H:i').').');
        }
    }

    private function assertCanDecideWinner(Competition $competition): void
    {
        if ($competition->status !== 'published') {
            throw new CompetitionRuleException('A winner can only be declared for a published competition.');
        }

        if (! $competition->hasFinished()) {
            throw new CompetitionRuleException('The winner can be declared once the competition has finished ('.$competition->end_time->format('M j, Y H:i').').');
        }
    }

    private function isConfirmed(Competition $competition, string $type, int $id): bool
    {
        return $competition->registrations()
            ->where('registrant_type', $type)
            ->where('registrant_id', $id)
            ->where('status', 'confirmed')
            ->exists();
    }

    private function addGame(object $row, int $scored, int $conceded): void
    {
        $row->played++;
        $row->goals_for += $scored;
        $row->goals_against += $conceded;
        $row->goal_difference = $row->goals_for - $row->goals_against;

        match ($scored <=> $conceded) {
            1 => [$row->won++, $row->points += self::POINTS_FOR_WIN],
            0 => [$row->drawn++, $row->points += self::POINTS_FOR_DRAW],
            -1 => $row->lost++,
        };
    }

    /**
     * What decides the order of two teams in the standings.
     *
     * @return array{int, int, int}
     */
    private function standingKey(object $row): array
    {
        return [$row->points, $row->goal_difference, $row->goals_for];
    }

    private function lock(Competition $competition): Competition
    {
        return RowLock::lock($competition) ?? throw new CompetitionRuleException('This competition no longer exists.');
    }
}
