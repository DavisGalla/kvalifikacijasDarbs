<?php

namespace App\Filament\Resources\Teams\Tables;

use App\Filament\Support\RosterChanges;
use App\Models\Team;
use App\Services\TeamRoster;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sport.name')->label('Sport')->sortable(),
                TextColumn::make('captain.name')->label('Captain')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('archived_at')->label('Archived')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Archived teams')
                    ->placeholder('Without archived teams')
                    ->trueLabel('With archived teams')
                    ->falseLabel('Only archived teams'),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete or archive selected')
                        ->using(fn (DeleteBulkAction $action, Collection $records) => RosterChanges::attemptEach(
                            $action,
                            $records,
                            fn (TeamRoster $roster, Team $team) => $roster->deleteTeam($team),
                        )),
                ]),
            ]);
    }
}
