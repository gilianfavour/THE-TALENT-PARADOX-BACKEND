<?php

namespace App\Filament\Resources\SkillChecks;

use App\Filament\Resources\SkillChecks\Pages\ListSkillChecks;
use App\Models\AssessmentAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only view of data captured on the candidate website. */
class SkillChecksResource extends Resource
{
    protected static ?string $model = AssessmentAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Skills checks';

    protected static ?string $modelLabel = 'skills check';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return 'Recruitment';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) AssessmentAttempt::count();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['candidate', 'job'])->latest('completed_at'))
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('job.title')->label('For vacancy')->placeholder('General'),
                TextColumn::make('overall_score')->label('Overall')->badge()->sortable()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 45 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state . '/100'),
                TextColumn::make('skills')->label('Skills scored')->badge()->color('gray')
                    ->getStateUsing(function (AssessmentAttempt $record) {
                        $names = \App\Models\Skill::whereIn('id', array_keys($record->skill_scores ?? []))->pluck('name', 'id');

                        return collect($record->skill_scores ?? [])->map(fn ($score, $id) => ($names[$id] ?? 'Skill') . ' ' . $score)->values()->all();
                    }),
                TextColumn::make('completed_at')->label('Completed')->since()->sortable(),
            ])
            ->emptyStateHeading('No skills checks yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkillChecks::route('/'),
        ];
    }
}
