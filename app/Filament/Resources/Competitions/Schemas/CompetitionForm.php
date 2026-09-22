<?php

namespace App\Filament\Resources\Competitions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class CompetitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organizer_id')
                    ->relationship('organizer', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('sport_id')
                    ->relationship('sport', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')->required()->maxLength(255),
                Textarea::make('description')->required()->columnSpanFull(),
                TextInput::make('location')->required()->maxLength(255),
                DateTimePicker::make('start_time')->required(),
                DateTimePicker::make('end_time')->required(),
                DateTimePicker::make('registration_deadline')->required(),
                TextInput::make('max_participants')->numeric()->minValue(1),
                Select::make('registration_mode')
                    ->options([
                        'individual' => 'Individual registration',
                        'team' => 'Team captain registration',
                    ])
                    ->default('individual')
                    ->live()
                    ->required(),
                TextInput::make('min_team_members')
                    ->numeric()
                    ->minValue(1)
                    ->visible(fn (Get $get) => $get('registration_mode') === 'team')
                    ->required(fn (Get $get) => $get('registration_mode') === 'team'),
                TextInput::make('max_team_members')
                    ->numeric()
                    ->minValue(1)
                    ->visible(fn (Get $get) => $get('registration_mode') === 'team')
                    ->required(fn (Get $get) => $get('registration_mode') === 'team')
                    ->gte('min_team_members'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
            ]);
    }
}
