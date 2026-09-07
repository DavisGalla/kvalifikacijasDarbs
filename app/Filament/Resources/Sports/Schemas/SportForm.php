<?php

namespace App\Filament\Resources\Sports\Schemas;

use Filament\Schemas\Schema;
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
            ]);
    }
}
