<?php

namespace App\Filament\Resources\Jobs\Tables;

use App\Services\JobParser;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('client_name')->label('Client')->searchable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'open',
                    'warning' => 'paused',
                    'gray' => ['filled', 'closed'],
                ]),
                TextColumn::make('deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->deadline && $record->deadline->isBefore(now()->addDays(7)) ? 'danger' : null),
                TextColumn::make('applications_count')
                    ->label('Applicants')
                    ->counts('applications')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('avg_score')
                    ->label('Avg AI score')
                    ->getStateUsing(fn ($record) => $record->applications()->avg('ai_score'))
                    ->formatStateUsing(fn ($state) => $state ? round($state) . '/100' : '-')
                    ->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'paused' => 'Paused',
                    'filled' => 'Filled',
                    'closed' => 'Closed',
                ]),
            ])
            ->recordActions([
                Action::make('analyze')
                    ->label('Re-analyse JD')
                    ->icon('heroicon-o-sparkles')
                    ->action(function ($record) {
                        $record->update(['parsed_requirements' => app(JobParser::class)->parse($record)]);
                        Notification::make()->title('Requirements extracted')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
