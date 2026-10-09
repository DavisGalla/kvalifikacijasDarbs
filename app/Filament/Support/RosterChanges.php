<?php

namespace App\Filament\Support;

use App\Exceptions\RosterChangeException;
use App\Models\TeamMember;
use App\Services\TeamRoster;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Routes admin panel team changes through TeamRoster, so admins get the same roster limits,
 * locking and archiving as everyone else, and refusals are shown as notifications.
 */
class RosterChanges
{
    /**
     * Run a roster change for a single-record action, halting the action when it is refused.
     */
    public static function attempt(Action $action, string $title, Closure $change): bool
    {
        try {
            $change(app(TeamRoster::class));
        } catch (RosterChangeException $e) {
            Notification::make()->danger()->title($title)->body($e->getMessage())->send();

            $action->halt();
        }

        // Filament treats a falsy result from a delete action's `using` callback as a failure.
        return true;
    }

    /**
     * Run a roster change per record for a bulk action, reporting each refusal with its reason.
     */
    public static function attemptEach(BulkAction $action, Collection $records, Closure $change): void
    {
        foreach ($records as $record) {
            try {
                $change(app(TeamRoster::class), $record);
            } catch (RosterChangeException $e) {
                $action->reportBulkProcessingFailure($e->getMessage(), $e->getMessage());
            }
        }
    }

    public static function removeMember(TeamRoster $roster, TeamMember $member): void
    {
        $team = $member->team ?? throw new RosterChangeException('The rosters of archived teams are kept as competition history.');

        $roster->removeMember($team, $member->user);
    }
}
