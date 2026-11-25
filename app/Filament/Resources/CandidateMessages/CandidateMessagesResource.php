<?php

namespace App\Filament\Resources\CandidateMessages;

use App\Filament\Resources\CandidateMessages\Pages\ListCandidateMessages;
use App\Models\CandidateNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only view of data captured on the candidate website. */
class CandidateMessagesResource extends Resource
{
    protected static ?string $model = CandidateNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Candidate updates';

    protected static ?string $modelLabel = 'candidate update';

    protected static ?int $navigationSort = 10;

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
            ->modifyQueryUsing(fn ($query) => $query->with('candidate')->latest())
            ->columns([
                TextColumn::make('created_at')->label('Sent')->since()->sortable(),
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('type')->badge()->color('info')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', (string) $state))),
                TextColumn::make('title')->wrap(),
                TextColumn::make('body')->wrap()->limit(90)->placeholder('-')->toggleable(),
                TextColumn::make('read_at')->label('Status')->badge()
                    ->getStateUsing(fn (CandidateNotification $record) => $record->read_at ? 'Read' : 'Unread')
                    ->color(fn ($state) => $state === 'Read' ? 'gray' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('type')->options(fn () => CandidateNotification::query()->distinct()->pluck('type', 'type')->all()),
            ])
            ->emptyStateHeading('No candidate updates yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidateMessages::route('/'),
        ];
    }
}
