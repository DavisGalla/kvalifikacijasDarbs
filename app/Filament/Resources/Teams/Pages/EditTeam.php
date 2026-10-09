<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Filament\Resources\Teams\TeamResource;
use App\Filament\Support\RosterChanges;
use App\Models\Team;
use App\Services\TeamRoster;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Delete or archive')
                ->modalDescription('A team that has taken part in competitions is archived instead, so its history is kept.')
                ->using(fn (DeleteAction $action, Team $record) => RosterChanges::attempt(
                    $action,
                    'Team not deleted',
                    fn (TeamRoster $roster) => $roster->deleteTeam($record),
                )),
            RestoreAction::make(),
        ];
    }
}
