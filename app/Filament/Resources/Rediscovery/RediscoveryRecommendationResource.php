<?php

namespace App\Filament\Resources\Rediscovery;

use App\Filament\Resources\Rediscovery\Pages\ListRediscoveryRecommendations;
use App\Filament\Resources\Rediscovery\Tables\RediscoveryTable;
use App\Models\RediscoveryRecommendation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RediscoveryRecommendationResource extends Resource
{
    protected static ?string $model = RediscoveryRecommendation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Talent Rediscovery';

    protected static ?string $modelLabel = 'alternative recommendation';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $n = RediscoveryRecommendation::where('status', 'pending_review')->count();

        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return RediscoveryTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRediscoveryRecommendations::route('/'),
        ];
    }
}
