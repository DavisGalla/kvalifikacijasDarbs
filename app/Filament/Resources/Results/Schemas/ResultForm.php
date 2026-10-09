<?php

namespace App\Filament\Resources\Results\Schemas;

use App\Exceptions\CompetitionRuleException;
use App\Models\Competition;
use App\Rules\ValidResult;
use App\Services\CompetitionResults;
use App\Support\ResultFormat;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->required()
                    ->rules([
                        // Same rule as the organizer's results page: only confirmed participants get results.
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $isConfirmed = Competition::find($get('competition_id'))?->registrations()
                                ->where('registrant_type', $get('registrant_type'))
                                ->where('registrant_id', $value)
                                ->where('status', 'confirmed')
                                ->exists();

                            if (! $isConfirmed) {
                                $fail('That participant is not confirmed for this competition.');
                            }
                        },
                    ]),
                TextInput::make('value')
                    ->label(fn (Get $get) => static::format($get('competition_id'))?->isTime() ? 'Time' : 'Result')
                    ->helperText(fn (Get $get) => static::format($get('competition_id'))?->description() ?? 'Choose a competition first.')
                    ->placeholder(fn (Get $get) => static::format($get('competition_id'))?->placeholder())
                    ->required()
                    ->rules(fn (Get $get) => ($format = static::format($get('competition_id'))) ? [new ValidResult($format)] : [])
                    ->formatStateUsing(fn ($state, Get $get) => static::format($get('competition_id'))?->inputValue($state) ?? $state)
                    ->dehydrateStateUsing(fn ($state, Get $get) => static::format($get('competition_id'))?->parse($state) ?? $state),
            ]);
    }

    protected static function format(?int $competitionId): ?ResultFormat
    {
        if (! $competitionId) {
            return null;
        }

        return Competition::with('sport')->find($competitionId)?->sport?->resultFormat();
    }
}
