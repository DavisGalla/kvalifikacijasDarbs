<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Support\RosterChanges;
use App\Models\TeamMember;
use App\Services\TeamRoster;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamMember extends EditRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(fn (DeleteAction $action, TeamMember $record) => RosterChanges::attempt(
                    $action,
                    'Member not removed',
                    fn (TeamRoster $roster) => RosterChanges::removeMember($roster, $record),
                )),
        ];
    }
}
