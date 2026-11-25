<?php

namespace App\Filament\Resources\Experiences;

use App\Filament\Resources\Experiences\Pages\ListExperiences;
use App\Models\Experience;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only view of data captured on the candidate website. */
class ExperiencesResource extends Resource
{
    protected static ?string $model = Experience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $navigationLabel = 'Experiences';

    protected static ?string $modelLabel = 'experience';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Talent';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Experience::count();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('candidate')->latest())
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('type')->badge()
                    ->formatStateUsing(fn ($state) => Experience::TYPES[$state] ?? ucfirst((string) $state))
                    ->color(fn ($state) => in_array($state, ['employment', 'internship'], true) ? 'gray' : 'primary'),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('organisation')->placeholder('-')->toggleable(),
                TextColumn::make('inferred_skills')->label('Skills discovered (AI-inferred)')->badge()->color('info')->placeholder('None yet'),
                TextColumn::make('evidence_url')->label('Evidence')->placeholder('-')
                    ->formatStateUsing(fn ($state) => $state ? 'Open link' : null)
                    ->url(fn ($record) => $record->evidence_url, true)->color('primary'),
                TextColumn::make('created_at')->label('Added')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(Experience::TYPES),
            ])
            ->emptyStateHeading('No experiences yet')
            ->emptyStateDescription('Projects, informal work and volunteering that candidates add in their portal appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExperiences::route('/'),
        ];
    }
}
