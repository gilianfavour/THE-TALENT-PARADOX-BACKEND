<?php

namespace App\Filament\Resources\Candidates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Candidate')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->email()->required(),
                        TextInput::make('phone'),
                        TextInput::make('location'),
                        TextInput::make('headline')->columnSpanFull(),
                        Select::make('status')->options([
                            'active' => 'Active',
                            'placed' => 'Placed',
                            'withdrawn' => 'Withdrawn',
                        ])->default('active')->required(),
                        TextInput::make('portal_token')->label('Portal token')->readOnly(),
                    ]),
                Section::make('CV text')
                    ->components([
                        Textarea::make('cv_text')
                            ->rows(10)
                            ->helperText('Raw CV text is parsed into claimed skills and profile signals.'),
                    ]),
            ]);
    }
}
