<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Exceptions\RosterChangeException;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamRoster;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;

    /**
     * Add the member through TeamRoster so competition roster limits and locking apply.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(TeamRoster::class)->addMember(
                Team::findOrFail($data['team_id']),
                User::findOrFail($data['user_id']),
                $data['role'],
                Carbon::parse($data['joined_at']),
            );
        } catch (RosterChangeException $e) {
            Notification::make()->danger()->title('Member not added')->body($e->getMessage())->send();

            $this->halt();
        }
    }
}
