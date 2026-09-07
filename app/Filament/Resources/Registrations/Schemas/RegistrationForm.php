<?php

namespace App\Filament\Resources\Registrations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('competition_id')
                    ->relationship('competition', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
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
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                DateTimePicker::make('registered_at')->required(),
            ]);
    }
}
