<?php

namespace App\Services;

use App\Exceptions\AccountDeletionException;
use App\Exceptions\RosterChangeException;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deletes a user account without breaking competition history.
 *
 * Registrations, results and winners point at users polymorphically, and organized competitions
 * and captained teams cascade on delete. A user who appears in any of those is therefore
 * anonymized (personal data scrubbed, row kept and soft-deleted) instead of deleted. Personal
 * content that is not shared history (posts, workouts, personal bests) is always removed.
 */
class AccountDeletion
{
    public function __construct(private TeamRoster $roster)
    {
    }

    /**
     * Returns true when the account was anonymized rather than deleted.
     *
     * @throws AccountDeletionException when upcoming commitments must be resolved first
     */
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $user = RowLock::lock($user) ?? throw new AccountDeletionException('This account no longer exists.');

            $this->assertNoUpcomingCommitments($user);

            try {
                // Teams go through the roster service, so they are archived when they have history
                // and the same roster rules and locks apply as everywhere else.
                foreach ($user->captainedTeams()->get() as $team) {
                    $this->roster->deleteTeam($team);
                }

                foreach (Team::whereHas('members', fn ($query) => $query->where('user_id', $user->id))->get() as $team) {
                    $this->roster->removeMember($team, $user);
                }
            } catch (RosterChangeException $e) {
                throw new AccountDeletionException($e->getMessage());
            }

            TeamInvitation::where('invited_user_id', $user->id)->orWhere('invited_by', $user->id)->delete();
            $user->officiatedCompetitions()->detach();
            // Drafts were never public, so nobody's history depends on them.
            $user->organizedCompetitions()->where('status', 'draft')->delete();
            $user->posts()->delete();
            $user->comments()->delete();
            $user->personalBests()->delete();
            $user->workoutLogs()->delete();
            $user->workoutDays()->delete();

            if (! $user->hasCompetitionHistory()) {
                $user->forceDelete();

                return false;
            }

            $this->anonymize($user);

            return true;
        }, 3);
    }

    private function assertNoUpcomingCommitments(User $user): void
    {
        $upcomingRegistration = $user->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('competition', fn ($query) => $query->where('start_time', '>', now()))
            ->with('competition')
            ->first();

        if ($upcomingRegistration) {
            throw new AccountDeletionException("You are registered for {$upcomingRegistration->competition->title}. Withdraw from upcoming competitions before deleting your account.");
        }

        $unfinishedCompetition = $user->organizedCompetitions()
            ->where('status', 'published')
            ->where('end_time', '>', now())
            ->first();

        if ($unfinishedCompetition) {
            throw new AccountDeletionException("You organize {$unfinishedCompetition->title}, which has not finished yet. Cancel it or wait until it ends before deleting your account.");
        }
    }

    private function anonymize(User $user): void
    {
        $user->forceFill([
            'name' => 'Deleted user',
            'username' => "deleted_{$user->id}",
            'email' => "deleted-{$user->id}@users.invalid",
            'password' => Str::random(64),
            'email_verified_at' => null,
            'remember_token' => null,
            'google_id' => null,
            'avatar' => null,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'is_admin' => false,
        ])->save();

        $user->delete();
    }
}
