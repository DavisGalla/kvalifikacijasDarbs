<?php

namespace App\Filament\Resources\Sports\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class SportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('result_type')
                    ->label('Result type')
                    ->options([
                        'score' => 'Score (highest wins)',
                        'time' => 'Time (fastest wins)',
                    ])
                    ->default('score')
                    ->required()
                    ->helperText('Determines how results are ranked and displayed for this sport.'),
            ]);
    }
}
