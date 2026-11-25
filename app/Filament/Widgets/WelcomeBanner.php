<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Interviews\InterviewsResource;
use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Rediscovery\RediscoveryRecommendationResource;
use App\Filament\Resources\SkillEvidence\SkillEvidenceResource;
use App\Models\Application;
use App\Models\CandidateSkill;
use App\Models\Interview;
use App\Models\Job;
use App\Models\RediscoveryRecommendation;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

/** Dashboard header: what needs a recruiter's attention right now, and where the pipeline stands. */
class WelcomeBanner extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public function render(): View
    {
        $now = now()->timezone('Africa/Kampala');
        $greeting = $now->hour < 12 ? 'Good morning' : ($now->hour < 17 ? 'Good afternoon' : 'Good evening');

        $tasks = [
            ['label' => 'New applications to review', 'count' => Application::whereIn('status', ['applied', 'screening'])->count(), 'url' => ApplicationResource::getUrl('index'), 'tone' => 'red'],
            ['label' => 'Alternative roles to approve', 'count' => RediscoveryRecommendation::where('status', 'pending_review')->count(), 'url' => RediscoveryRecommendationResource::getUrl('index'), 'tone' => 'dark'],
            ['label' => 'Assessed skills to validate', 'count' => CandidateSkill::whereNotNull('demonstrated_score')->whereNull('validated_at')->count(), 'url' => SkillEvidenceResource::getUrl('index'), 'tone' => 'dark'],
            ['label' => 'Upcoming interviews', 'count' => Interview::where('scheduled_at', '>=', now())->count(), 'url' => InterviewsResource::getUrl('index'), 'tone' => 'dark'],
            ['label' => 'Vacancies closing within 7 days', 'count' => Job::open()->whereNotNull('deadline')->where('deadline', '<=', now()->addDays(7))->count(), 'url' => JobResource::getUrl('index'), 'tone' => 'red'],
        ];

        $counts = Application::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $pipeline = collect(Application::STATUSES)
            ->map(fn ($label, $key) => ['label' => $label, 'count' => (int) ($counts[$key] ?? 0)])
            ->filter(fn ($s) => $s['count'] > 0)
            ->values()
            ->all();

        return view('filament.widgets.welcome-banner', [
            'greeting' => $greeting,
            'name' => explode(' ', auth()->user()?->name ?? 'Recruiter')[0],
            'date' => $now->format('l j F Y'),
            'tasks' => $tasks,
            'pipeline' => $pipeline,
            'total' => array_sum(array_column($pipeline, 'count')),
            'newVacancyUrl' => JobResource::getUrl('create'),
        ]);
    }
}
