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
                Select::make('result_decimals')
                    ->label('Result precision')
                    ->options([
                        0 => 'Whole numbers',
                        1 => '1 decimal',
                        2 => '2 decimals (e.g. hundredths of a second)',
                        3 => '3 decimals (e.g. thousandths of a second)',
                    ])
                    ->placeholder('Default: 2 decimals for time, whole numbers for score')
                    ->helperText('How precisely results may be entered. Results with more decimals are rejected.'),
                TextInput::make('result_max')
                    ->label('Maximum result')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(9999999.999)
                    ->step(0.001)
                    ->helperText('Optional upper bound, in seconds for time or points for score (e.g. 10 for gymnastics).'),
            ]);
    }
}
