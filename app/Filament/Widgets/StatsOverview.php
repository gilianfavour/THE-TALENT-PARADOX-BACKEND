<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Job;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $apps = Application::query();
        $avg = $apps->avg('ai_score');
        $trend = collect(range(6, 0))
            ->map(fn ($d) => Application::whereDate('created_at', now()->subDays($d)->toDateString())->count())
            ->all();

        return [
            Stat::make('Open vacancies', Job::open()->count())
                ->description('across ' . Job::open()->distinct('client_name')->count() . ' clients')
                ->icon('heroicon-o-briefcase'),
            Stat::make('Active candidates', Candidate::where('status', 'active')->count())
                ->icon('heroicon-o-user-group'),
            Stat::make('Applications in pipeline', $apps->count())
                ->description('avg compatibility ' . ($avg ? round($avg) : '—') . '/100')
                ->chart($trend)
                ->color('primary')
                ->icon('heroicon-o-inbox-stack'),
            Stat::make('Hidden gems surfaced', Application::where('match_snapshot', 'like', '%hidden_gem%')->count())
                ->description('would have been missed by CV screening')
                ->icon('heroicon-o-sparkles')
                ->color('warning'),
            Stat::make('Recruiter decisions', Decision::count())
                ->description('human judgement is final')
                ->icon('heroicon-o-scale'),
            Stat::make('Rediscovery awaiting review', \App\Models\RediscoveryRecommendation::where('status', 'pending_review')->count())
                ->description(\App\Models\RediscoveryRecommendation::where('status', 'invited')->count() . ' candidates invited to other roles')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->url(\App\Filament\Resources\Rediscovery\RediscoveryRecommendationResource::getUrl('index')),
        ];
    }
}
