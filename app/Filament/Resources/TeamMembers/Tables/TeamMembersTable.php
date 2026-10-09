<?php

namespace App\Filament\Resources\TeamMembers\Tables;

use App\Filament\Support\RosterChanges;
use App\Models\TeamMember;
use App\Services\TeamRoster;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team.name')->label('Team')->sortable(),
                TextColumn::make('user.name')->label('Member')->sortable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('joined_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(fn (DeleteBulkAction $action, Collection $records) => RosterChanges::attemptEach(
                            $action,
                            $records,
                            fn (TeamRoster $roster, TeamMember $member) => RosterChanges::removeMember($roster, $member),
                        )),
                ]),
            ]);
    }
}
