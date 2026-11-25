<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Services\MatchEngine;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('job.title')->label('Applied for')->searchable()->sortable(),
                TextColumn::make('job.client_name')->label('Client'),
                TextColumn::make('ai_score')
                    ->label('AI score')
                    ->badge()
                    ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 55 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '/100' : 'pending')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'applied',
                        'info' => 'screening',
                        'primary' => 'shortlisted',
                        'warning' => 'interview',
                        'success' => ['offer', 'hired'],
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => \App\Models\Application::STATUSES[$state] ?? ucfirst($state)),
                TextColumn::make('signals')
                    ->label('Signals')
                    ->getStateUsing(fn ($record) => implode(', ', $record->match_snapshot['signals'] ?? []))
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('created_at')->label('Applied')->date('d M')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(\App\Models\Application::STATUSES),
                SelectFilter::make('job')->relationship('job', 'title'),
            ])
            ->recordActions([
                Action::make('match_analysis')
                    ->label('Why this score?')
                    ->icon('heroicon-o-sparkles')
                    ->modalHeading(fn ($record) => "Why {$record->candidate->name} scored {$record->ai_score}/100 for {$record->job->title}")
                    ->modalContent(fn ($record) => view('filament.partials.application-analysis', ['record' => $record]))
                    ->color('info'),
                Action::make('advance')
                    ->label('Advance stage')
                    ->icon('heroicon-o-arrow-right')
                    ->form([Textarea::make('reason')->label('Recruiter note (recorded)')->rows(2)])
                    ->action(function ($record, array $data) {
                        $next = [
                            'applied' => 'screening',
                            'screening' => 'shortlisted',
                            'shortlisted' => 'interview',
                            'interview' => 'offer',
                            'offer' => 'hired',
                        ][$record->status] ?? 'shortlisted';
                        $record->update(['status' => $next]);
                        $record->candidate->decisions()->create([
                            'application_id' => $record->id, 'job_id' => $record->job_id,
                            'action' => $next, 'reason' => $data['reason'] ?? null, 'recruiter' => 'admin',
                        ]);
                        app(MatchEngine::class)->learnFromDecision($next, $record->match_snapshot ?? []);
                        Notification::make()->title("Moved to {$next}")->success()->send();
                    })
                    ->visible(fn ($record) => ! in_array($record->status, ['hired', 'rejected'])),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The AI recommendation is advisory only. Your decision is final and is recorded.')
                    ->form([Textarea::make('reason')->label('Reason (optional, recorded)')->rows(2)])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => 'rejected']);
                        $record->candidate->decisions()->create([
                            'application_id' => $record->id, 'job_id' => $record->job_id,
                            'action' => 'reject', 'reason' => $data['reason'] ?? null, 'recruiter' => 'admin',
                        ]);
                        app(MatchEngine::class)->learnFromDecision('reject', $record->match_snapshot ?? []);
                        Notification::make()->title('Rejected - decision recorded')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ai_score', 'desc');
    }
}
