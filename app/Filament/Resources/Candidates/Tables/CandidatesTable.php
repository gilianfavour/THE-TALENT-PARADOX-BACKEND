<?php

namespace App\Filament\Resources\Candidates\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('location')->searchable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'info' => 'placed',
                    'gray' => 'withdrawn',
                ]),
                TextColumn::make('skills_count')
                    ->label('Skills')
                    ->counts('skills')
                    ->badge(),
                TextColumn::make('applications_count')
                    ->label('Applications')
                    ->counts('applications')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('hidden_gem')
                    ->label('Signal')
                    ->getStateUsing(function ($record) {
                        $flags = $record->profile_flags ?? [];
                        $hasDem = $record->skills()->whereNotNull('demonstrated_score')->count();
                        if ($hasDem >= 2 && ($flags['early_career'] ?? false) === false) return 'hidden gem?';
                        if ($flags['career_changer'] ?? false) return 'career changer';
                        return null;
                    })
                    ->badge()
                    ->color('warning'),
            ])
            ->recordActions([
                Action::make('analysis')
                    ->label('Match analysis')
                    ->icon('heroicon-o-chart-bar')
                    ->modalHeading(fn ($record) => "Match analysis: {$record->name}")
                    ->modalContent(fn ($record) => view('filament.partials.candidate-analysis', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
