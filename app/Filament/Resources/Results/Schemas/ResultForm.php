<?php

namespace App\Filament\Resources\Results\Schemas;

use App\Models\Competition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class ResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('competition_id')
                    ->relationship('competition', 'title')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('registrant_type')
                    ->options([
                        'user' => 'User',
                        'team' => 'Team',
                    ])
                    ->required(),
                TextInput::make('registrant_id')
                    ->label('Registrant ID')
                    ->numeric()
                    ->required(),
                TextInput::make('value')
                    ->label(fn (Get $get) => static::resultType($get('competition_id')) === 'time'
                        ? 'Time (seconds)'
                        : 'Score')
                    ->helperText(fn (Get $get) => static::resultType($get('competition_id')) === 'time'
                        ? 'Enter the finishing time in seconds, e.g. 63.482 for 1:03.482.'
                        : 'Enter the final score. Highest score wins.')
                    ->numeric()
                    ->step(0.001)
                    ->required(),
            ]);
    }

    protected static function resultType(?int $competitionId): ?string
    {
        if (! $competitionId) {
            return null;
        }

        return Competition::find($competitionId)?->sport?->result_type;
    }
}
