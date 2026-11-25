<?php

namespace App\Filament\Widgets;

use App\Models\Job;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DeadlineWatch extends TableWidget
{
    protected static ?string $heading = 'Client deadlines approaching';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Job::open()->orderBy('deadline'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')->weight('semibold'),
                \Filament\Tables\Columns\TextColumn::make('client_name')->label('Client'),
                \Filament\Tables\Columns\TextColumn::make('applications_count')->counts('applications')->label('Apps')->badge(),
                \Filament\Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->deadline && $record->deadline->isBefore(now()->addDays(7)) ? 'danger' : 'success'),
                \Filament\Tables\Columns\TextColumn::make('days')
                    ->label('Days left')
                    ->getStateUsing(fn ($record) => $record->deadline ? (int) round(now()->diffInDays($record->deadline, false)) : null)
                    ->badge()
                    ->color(fn ($state) => $state <= 7 ? 'danger' : 'success'),
            ]);
    }
}
