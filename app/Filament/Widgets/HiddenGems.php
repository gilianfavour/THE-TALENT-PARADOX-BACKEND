<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HiddenGems extends TableWidget
{
    protected static ?string $heading = 'Hidden gems - overlooked by CVs, surfaced by evidence';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Application::with(['candidate', 'job'])
                ->where('match_snapshot', 'like', '%hidden_gem%')
                ->orWhere('match_snapshot', 'like', '%better_fit_elsewhere%'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('candidate.name')->weight('semibold'),
                \Filament\Tables\Columns\TextColumn::make('job.title')->label('Applied for'),
                \Filament\Tables\Columns\TextColumn::make('better_fit')
                    ->label('Better fit')
                    ->getStateUsing(fn ($record) => ($record->match_snapshot['better_fit']['title'] ?? null) ?: '-'),
                \Filament\Tables\Columns\TextColumn::make('ai_score')->label('Score')->badge()->color('warning'),
            ]);
    }
}
