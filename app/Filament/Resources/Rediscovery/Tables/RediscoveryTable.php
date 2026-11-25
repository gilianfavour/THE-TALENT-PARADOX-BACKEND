<?php

namespace App\Filament\Resources\Rediscovery\Tables;

use App\Models\RediscoveryRecommendation;
use App\Services\RediscoveryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RediscoveryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('application.job.title')->label('Originally applied for')->placeholder('-'),
                TextColumn::make('original_score')->label('Original')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '/100' : '-'),
                TextColumn::make('job.title')->label('Suggested vacancy')->weight('semibold'),
                TextColumn::make('score')->label('Compatibility')->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state . '/100')->sortable(),
                TextColumn::make('reasons')->label('Why')
                    ->getStateUsing(fn ($record) => implode(' | ', array_slice($record->reasons ?? [], 0, 2)))
                    ->wrap()->limit(140),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending_review' => 'warning',
                        'invited' => 'info',
                        'accepted' => 'success',
                        'declined', 'dismissed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => RediscoveryRecommendation::STATUSES[$state] ?? $state),
                TextColumn::make('reviewed_by')->label('Reviewed by')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(RediscoveryRecommendation::STATUSES),
            ])
            ->recordActions([
                Action::make('invite')
                    ->label('Approve and invite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('The candidate is notified and can accept or decline. Nothing is applied for on their behalf.')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->action(function ($record) {
                        app(RediscoveryService::class)->invite($record, auth()->user()?->name ?? 'admin');
                        Notification::make()->title('Candidate invited')->success()->send();
                    }),
                Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->action(function ($record) {
                        app(RediscoveryService::class)->dismiss($record, auth()->user()?->name ?? 'admin');
                        Notification::make()->title('Recommendation dismissed')->send();
                    }),
            ])
            ->defaultSort('score', 'desc');
    }
}
