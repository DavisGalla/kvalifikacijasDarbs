<?php

namespace App\Filament\Resources\Teams\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                Select::make('sport_id')
                    ->relationship('sport', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('captain_id')
                    ->relationship('captain', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
