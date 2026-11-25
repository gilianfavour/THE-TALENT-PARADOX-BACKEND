<?php

namespace App\Console\Commands;

use App\Models\Experience;
use App\Models\Job;
use App\Services\Ai\AiInsights;
use App\Services\Ai\Claude;
use App\Services\JobParser;
use App\Services\MatchEngine;
use Illuminate\Console\Command;

class AiCheck extends Command
{
    protected $signature = 'ai:check {--refresh : Re-analyse every vacancy with AI and rescore applications}';

    protected $description = 'Check the Claude connection and optionally re-analyse vacancies with AI';

    public function handle(Claude $claude, AiInsights $ai): int
    {
        if (! $claude->enabled()) {
            $this->warn('No ANTHROPIC_API_KEY set. TalentBridge is running on the rule-based fallback.');

            return self::SUCCESS;
        }

        $this->info('Engine: ' . $claude->label());
        $probe = $ai->skillsFromText('I ran stock and daily sales records in my family shop and coordinated 30 volunteers for a clean-up.', 'connection check');
        if ($probe === null) {
            $this->error('Claude did not answer. Check the key, model name and network. Falling back to rules.');

            return self::FAILURE;
        }
        $this->info('Connected. Sample skills found: ' . collect($probe)->pluck('name')->implode(', '));

        if ($this->option('refresh')) {
            $parser = app(JobParser::class);
            foreach (Job::all() as $job) {
                $job->update(['parsed_requirements' => $parser->parse($job)]);
                $this->line(" - analysed {$job->title}");
            }
            $n = app(MatchEngine::class)->refreshAll(0);
            $this->info("Rescored {$n} applications.");
        }

        return self::SUCCESS;
    }
}
