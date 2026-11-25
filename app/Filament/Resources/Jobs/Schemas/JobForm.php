<?php

namespace App\Filament\Resources\Jobs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vacancy')
                    ->columns(2)
                    ->components([
                        TextInput::make('title')->required()->maxLength(255),
                        TextInput::make('client_name')->label('Client')->maxLength(255),
                        TextInput::make('location'),
                        Select::make('employment_type')->options([
                            'Full-time' => 'Full-time',
                            'Part-time' => 'Part-time',
                            'Contract' => 'Contract',
                            'Internship' => 'Internship',
                        ])->default('Full-time'),
                        TextInput::make('salary_min')->numeric()->prefix('TZS'),
                        TextInput::make('salary_max')->numeric()->prefix('TZS'),
                        DatePicker::make('deadline'),
                        Select::make('status')->options([
                            'open' => 'Open',
                            'paused' => 'Paused',
                            'filled' => 'Filled',
                            'closed' => 'Closed',
                        ])->default('open')->required(),
                    ]),
                Section::make('Job description')
                    ->components([
                        Textarea::make('description')
                            ->rows(8)
                            ->helperText('Paste the client job description. The engine extracts competencies from "Requirements" and "Nice to have" sections.'),
                        Textarea::make('requirements_text')
                            ->label('Requirements (optional override)')
                            ->rows(4)
                            ->helperText('If set, only this text is analysed instead of the full description.'),
                    ]),
            ]);
    }
}
