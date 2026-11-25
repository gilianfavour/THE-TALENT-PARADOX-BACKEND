<?php

namespace App\Filament\Resources\Decisions;

use App\Filament\Resources\Decisions\Pages\ListDecisions;
use App\Models\Decision;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only view of data captured on the candidate website. */
class DecisionsResource extends Resource
{
    protected static ?string $model = Decision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Decision log';

    protected static ?string $modelLabel = 'decision';

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return 'Oversight';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['candidate', 'job'])->latest())
            ->columns([
                TextColumn::make('created_at')->label('When')->since()->sortable(),
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('job.title')->label('Vacancy'),
                TextColumn::make('action')->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', (string) $state)))
                    ->color(fn ($state) => match (true) {
                        in_array($state, ['reject', 'reject_ai', 'rejected'], true) => 'danger',
                        in_array($state, ['offer', 'hired'], true) => 'success',
                        str_contains((string) $state, 'rediscovery') => 'info',
                        default => 'primary',
                    }),
                TextColumn::make('reason')->wrap()->limit(100)->placeholder('-'),
                TextColumn::make('recruiter')->badge()->color('gray'),
            ])
            ->emptyStateHeading('No decisions recorded yet')
            ->emptyStateDescription('Every shortlist, interview, rejection and invitation made by a recruiter is recorded here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDecisions::route('/'),
        ];
    }
}
