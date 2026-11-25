<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Rediscovery\RediscoveryRecommendationResource;
use App\Models\RediscoveryRecommendation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RediscoveryQueue extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Talent Rediscovery - alternative roles waiting for your review';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => RediscoveryRecommendation::with(['candidate', 'job', 'application.job'])
                ->whereIn('status', ['pending_review', 'invited'])
                ->orderByRaw("case when status = 'pending_review' then 0 else 1 end")
                ->orderByDesc('score'))
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No alternative matches waiting')
            ->emptyStateDescription('Weak matches are checked against every open vacancy automatically.')
            ->recordUrl(fn () => RediscoveryRecommendationResource::getUrl('index'))
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->weight('semibold'),
                TextColumn::make('application.job.title')->label('Applied for')->placeholder('-'),
                TextColumn::make('original_score')->label('Original')->badge()->color('gray'),
                TextColumn::make('job.title')->label('Suggested role')->weight('semibold'),
                TextColumn::make('score')->label('Compatibility')->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : 'warning'),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => $state === 'pending_review' ? 'warning' : 'info')
                    ->formatStateUsing(fn ($state) => RediscoveryRecommendation::STATUSES[$state] ?? $state),
            ]);
    }
}
