<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required()
                    // Moving a membership would bypass the roster rules; remove and re-add instead.
                    ->disabledOn('edit')
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->disabledOn('edit')
                    ->searchable()
                    ->preload(),
                Select::make('role')
                    ->options([
                        'captain' => 'Captain',
                        'member' => 'Member',
                    ])
                    ->required(),
                DateTimePicker::make('joined_at')->required(),
            ]);
    }
}
