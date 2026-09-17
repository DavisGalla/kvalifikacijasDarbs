<?php

namespace App\Filament\Resources\Results\Tables;

use App\Models\Result;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('competition.title')->label('Competition')->sortable()->searchable(),
                TextColumn::make('competition.sport.name')->label('Sport')->badge(),
                TextColumn::make('registrant_type')->label('Type')->badge(),
                TextColumn::make('registrant')
                    ->label('Registrant')
                    ->state(fn (Result $record) => $record->registrant?->name ?? $record->registrant_id),
                TextColumn::make('value')
                    ->label('Result')
                    ->state(fn (Result $record) => $record->formattedValue())
                    ->sortable(),
                TextColumn::make('position')
                    ->badge()
                    ->color(fn (?int $state) => match ($state) {
                        1 => 'success',
                        2, 3 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('position')
            ->filters([
                SelectFilter::make('competition_id')
                    ->label('Competition')
                    ->relationship('competition', 'title'),
                SelectFilter::make('registrant_type')->options([
                    'user' => 'User',
                    'team' => 'Team',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
