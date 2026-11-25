<?php

namespace App\Filament\Resources\Rediscovery\Pages;

use App\Filament\Resources\Rediscovery\RediscoveryRecommendationResource;
use App\Services\RediscoveryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRediscoveryRecommendations extends ListRecords
{
    protected static string $resource = RediscoveryRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')
                ->label('Scan talent pool now')
                ->icon('heroicon-o-magnifying-glass')
                ->action(function () {
                    $n = app(RediscoveryService::class)->scanAll();
                    Notification::make()->title("{$n} alternative matches found or refreshed")->success()->send();
                }),
        ];
    }
}
