<?php

namespace App\Services;

use App\Exceptions\RosterChangeException;
use App\Models\Competition;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\RowLock;
use Closure;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The single entry point for changing who is on a team.
 *
 * Every roster change and every roster check (such as registering the team for a competition)
 * runs while holding the same lock on the team row. Concurrent requests touching one team are
 * therefore serialized, and the size a check sees is still the size when its transaction commits.
 */
class TeamRoster
{
    /**
     * Run the callback in a transaction while holding the team's roster lock.
     *
     * @template TReturn
     *
     * @param  Closure(Team): TReturn  $callback
     * @return TReturn
     */
    public function locked(Team $team, Closure $callback): mixed
    {
        return DB::transaction(function () use ($team, $callback) {
            $lockedTeam = RowLock::lock($team) ?? throw new RosterChangeException('This team no longer exists.');

            return $callback($lockedTeam);
        }, 3);
    }

    public function addMember(Team $team, User $user, string $role = 'member', ?DateTimeInterface $joinedAt = null): TeamMember
    {
        try {
            return $this->locked($team, function (Team $team) use ($user, $role, $joinedAt): TeamMember {
                // Under the roster lock a concurrent join for the same user has either committed
                // already (and is seen here) or is still waiting for the lock.
                if ($team->members()->where('user_id', $user->id)->exists()) {
                    throw new RosterChangeException('You are already a member of this team.');
                }

                $size = $team->members()->count();
                $this->assertSizeAllowed($team, $size, $size + 1);

                return $team->members()->create([
                    'user_id' => $user->id,
                    'role' => $role,
                    'joined_at' => $joinedAt ?? now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // The unique (team_id, user_id) index is the final guarantee should any write path
            // bypass the lock; report it like the regular duplicate instead of failing.
            throw new RosterChangeException('You are already a member of this team.');
        }
    }

    public function removeMember(Team $team, User $user): void
    {
        $this->locked($team, function (Team $team) use ($user): void {
            $member = $team->members()
                ->where('user_id', $user->id)
                ->first() ?? throw new RosterChangeException('You are not a member of this team.');

            if ($member->role === 'captain' || $team->captain_id === $user->id) {
                throw new RosterChangeException('The team captain cannot leave the team.');
            }

            $size = $team->members()->count();
            $this->assertSizeAllowed($team, $size, $size - 1);

            $member->delete();
        });
    }

    /**
     * Delete a team, or archive it when competition records refer to it so that history is kept.
     * Returns true when the team was archived rather than deleted.
     *
     * Runs under the roster lock, so a registration in progress for this team either commits
     * first (and blocks the deletion) or finds the team gone once it gets the lock.
     */
    public function deleteTeam(Team $team): bool
    {
        return $this->locked($team, function (Team $team): bool {
            if ($team->upcomingRegistrations()->exists()) {
                throw new RosterChangeException('This team is registered for an upcoming competition. Withdraw it from the competition before deleting the team.');
            }

            if (! $team->hasCompetitionHistory()) {
                $team->forceDelete();

                return false;
            }

            // Outstanding invitations would otherwise let people join a team that no longer exists.
            $team->invitations()->where('status', 'pending')->delete();
            $team->delete();

            return true;
        });
    }

    /**
     * Refuse a roster size that would break the limits of any upcoming competition the team is
     * registered for. Only the limit the change moves towards is enforced, so a team that is
     * already out of bounds can still move back within them.
     */
    private function assertSizeAllowed(Team $team, int $currentSize, int $newSize): void
    {
        $competitions = Competition::query()
            ->whereIn('id', $team->upcomingRegistrations()->select('competition_id'))
            ->orderBy('start_time')
            ->get();

        foreach ($competitions as $competition) {
            if ($newSize > $currentSize && $competition->max_team_members !== null && $newSize > $competition->max_team_members) {
                throw new RosterChangeException("This team is registered for {$competition->title}, which allows at most {$competition->max_team_members} members.");
            }

            if ($newSize < $currentSize && $competition->min_team_members !== null && $newSize < $competition->min_team_members) {
                throw new RosterChangeException("This team is registered for {$competition->title}, which requires at least {$competition->min_team_members} members.");
            }
        }
    }
}
