<?php

namespace App\Filament\Resources\Competitions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

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
