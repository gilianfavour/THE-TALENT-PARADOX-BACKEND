<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('candidate_id')
                    ->relationship('candidate', 'name')
                    ->searchable()
                    ->required(),
                Select::make('job_id')
                    ->relationship('job', 'title')
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(\App\Models\Application::STATUSES)
                    ->required()
                    ->default('applied'),
            ]);
    }
}
