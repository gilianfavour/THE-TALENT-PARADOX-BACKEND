<?php

namespace App\Filament\Resources\Interviews;

use App\Filament\Resources\Interviews\Pages\ListInterviews;
use App\Models\Interview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only view of data captured on the candidate website. */
class InterviewsResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Interviews';

    protected static ?string $modelLabel = 'interview';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return 'Recruitment';
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Interview::where('scheduled_at', '>=', now())->count();

        return $n ? (string) $n : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['application.candidate', 'application.job'])->orderBy('scheduled_at'))
            ->columns([
                TextColumn::make('application.candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('application.job.title')->label('Vacancy'),
                TextColumn::make('scheduled_at')->label('When')->dateTime('D d M, H:i')->sortable()
                    ->description(fn (Interview $record) => $record->scheduled_at?->diffForHumans()),
                TextColumn::make('type')->badge()->color('warning')
                    ->formatStateUsing(fn ($state) => match ($state) { 'phone' => 'Phone', 'video' => 'Video call', default => 'In person' }),
                TextColumn::make('outcome')->badge()->placeholder('Pending')
                    ->color(fn ($state) => in_array($state, ['passed', 'hired', 'recommended'], true) ? 'success' : 'gray'),
                TextColumn::make('notes')->wrap()->limit(80)->placeholder('-'),
            ])
            ->emptyStateHeading('No interviews scheduled');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterviews::route('/'),
        ];
    }
}
