<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\Job;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

/**
 * SecondLook Match Engine.
 *
 * Philosophy: the CV is evidence of *claims*; assessments are evidence of
 * *demonstrated* ability. Demonstrated evidence always outweighs claims.
 * Nothing here auto-rejects anyone - it ranks, explains, and flags, and a
 * human recruiter always makes the final call.
 */
class MatchEngine
{
    /** Base weights - adjusted slightly by recruiter decisions (learning loop). */
    private const BASE_WEIGHTS = [
        'skills' => 55,
        'experience' => 15,
        'education' => 10,
        'potential' => 20,
    ];

    private const DEMONSTRATED_THRESHOLD = 55;
    private const DEMONSTRATED_MIN = 30;

    public function __construct(
        private CvParser $cvParser,
        private JobParser $jobParser,
    ) {}

    /** Effective weights, nudged by what recruiters have actually approved. */
    public function weights(): array
    {
        $learned = SystemSetting::get('match_weights', []);
        $w = self::BASE_WEIGHTS;
        foreach (['skills', 'experience', 'education', 'potential'] as $k) {
            if (isset($learned[$k])) {
                $w[$k] = max(5, min(60, $w[$k] + $learned[$k]));
            }
        }
        return $w;
    }

    /**
     * Score one candidate against one job.
     * @return array{score:int, breakdown:array, signals:array, explanations:array}
     */
    public function score(Candidate $candidate, Job $job): array
    {
        $reqs = $job->parsed_requirements ?: $this->jobParser->parse($job);
        $weights = $this->weights();

        $skills = $candidate->skills()->with('skill')->get()->keyBy('skill_id');
        $flags = $candidate->profile_flags ?? [];

        // ---------- skills component ----------
        $skillEntries = [];
        $earned = 0.0;
        $possible = 0.0;
        $demonstratedCount = 0;
        $claimedCount = 0;
        $demonstratedSum = 0;
        $missingRequired = [];

        $allReqs = array_merge($reqs['required_skills'] ?? [], $reqs['preferred_skills'] ?? []);

        foreach ($allReqs as $req) {
            $weight = ($req['importance'] ?? 'required') === 'required' ? 1.0 : 0.45;
            $possible += $weight;
            $cs = $skills->get($req['skill_id']);

            $dem = $cs?->demonstrated_score;
            $hasClaim = $cs && ($cs->claimed_years !== null || $cs->claimed_evidence);

            if ($dem !== null && $dem >= self::DEMONSTRATED_THRESHOLD) {
                $value = 1.0;
                $demonstratedCount++;
                $demonstratedSum += $dem;
                $skillEntries[] = [
                    'name' => $req['name'], 'importance' => $req['importance'],
                    'status' => 'demonstrated', 'score' => $dem,
                    'note' => "Demonstrated {$dem}/100 via assessment",
                ];
            } elseif ($dem !== null && $dem >= self::DEMONSTRATED_MIN) {
                $value = 0.55;
                $demonstratedCount++;
                $demonstratedSum += $dem;
                $skillEntries[] = [
                    'name' => $req['name'], 'importance' => $req['importance'],
                    'status' => 'partial', 'score' => $dem,
                    'note' => "Partial evidence {$dem}/100",
                ];
            } elseif ($hasClaim) {
                // claims count, but less than proof
                $claimStrength = 0.55 + min(0.35, (($cs->claimed_years ?? 1) / 12) * 0.35);
                $value = $claimStrength * 0.8; // claims capped below demonstrated
                $claimedCount++;
                $skillEntries[] = [
                    'name' => $req['name'], 'importance' => $req['importance'],
                    'status' => 'claimed', 'score' => null,
                    'note' => $cs->claimed_years
                        ? "Claims {$cs->claimed_years} yrs (CV evidence only)"
                        : 'Claimed on CV - not yet assessed',
                ];
            } else {
                $value = 0.0;
                if (($req['importance'] ?? 'required') === 'required') {
                    $missingRequired[] = $req['name'];
                }
                $skillEntries[] = [
                    'name' => $req['name'], 'importance' => $req['importance'],
                    'status' => 'missing', 'score' => null,
                    'note' => 'No evidence found',
                ];
            }
            $earned += $value * $weight;
        }

        $skillsRatio = $possible > 0 ? $earned / $possible : 0;
        $skillsScore = round($skillsRatio * $weights['skills']);

        // ---------- experience ----------
        $reqYears = $reqs['years_min'] ?? 0;
        $candYears = $flags['years_experience'] ?? $candidate->skills()->max('claimed_years') ?? 0;
        $expRatio = $reqYears > 0 ? min(1.2, $candYears / $reqYears) : 1.0;
        $experienceScore = round(min(1.0, $expRatio) * $weights['experience']);

        // ---------- education ----------
        $eduRank = ['Secondary' => 1, 'Certificate' => 2, 'Diploma' => 3, "Bachelor's" => 4, "Master's" => 5, 'PhD' => 6];
        $reqEdu = $reqs['education_min'] ?? null;
        $candEdu = $flags['education'] ?? null;
        $eduScore = $weights['education'];
        if ($reqEdu && $candEdu) {
            $eduScore = (($eduRank[$candEdu] ?? 0) >= ($eduRank[$reqEdu] ?? 0))
                ? $weights['education']
                : round($weights['education'] * 0.35);
        } elseif ($reqEdu && ! $candEdu) {
            $eduScore = round($weights['education'] * 0.5);
        }

        // ---------- potential ----------
        $potential = 0;
        $signals = [];
        $explanations = [];

        $claimedCoverage = $claimedCount / max(1, count($allReqs));
        $demAvg = $demonstratedCount ? $demonstratedSum / $demonstratedCount : 0;

        // Hidden gem: real ability far above what the CV advertises
        if ($demonstratedCount >= 2 && $demAvg >= 60 && $claimedCoverage < 0.5) {
            $potential += 12;
            $signals[] = 'hidden_gem';
            $explanations[] = 'Hidden gem: assessed skills far exceed what the CV suggests - worth a conversation';
        }
        // Claims exceed evidence (protects against keyword-stuffed CVs too)
        if ($claimedCount >= 3 && $demonstratedCount === 0) {
            $signals[] = 'claims_unverified';
            $explanations[] = 'Several skills are CV claims only - recommend a skills check before interview';
        }
        // Transferable demonstrated skills outside the job's core category
        $jobCategory = $reqs['required_skills'][0]['category'] ?? null;
        if ($demonstratedCount >= 3) {
            $potential += 4;
            $signals[] = 'strong_demonstrated';
            $explanations[] = "{$demonstratedCount} skills verified by assessment (avg " . round($demAvg) . '/100)";
        }
        if (! empty($flags['career_changer'])) {
            $potential += 4;
            $signals[] = 'career_changer';
            $explanations[] = 'Career changer - evaluate transferable skills, not just titles';
        }
        if (! empty($flags['self_taught'])) {
            $potential += 3;
            $signals[] = 'self_taught';
            $explanations[] = 'Self-taught background - practical assessment recommended';
        }
        // Employment gaps are NEVER penalised - noted as resilience context instead
        if (! empty($flags['employment_gap'])) {
            $signals[] = 'career_gap';
            $explanations[] = 'Career break on record - not scored against the candidate';
        }
        if ($reqYears > 0 && $candYears > $reqYears + 3) {
            $potential += 2;
            $signals[] = 'overqualified';
            $explanations[] = "Overqualified on paper ({$candYears} yrs vs {$reqYears} required) - check motivation fit";
        }
        if ($reqYears === 0 && $flags['early_career'] ?? false) {
            $signals[] = 'early_career';
            $explanations[] = 'Early-career candidate - potential over pedigree';
        }

        $potentialScore = min($weights['potential'], $potential);

        $total = max(0, min(100, $skillsScore + $experienceScore + $eduScore + $potentialScore));

        // ---------- explanations ----------
        $topSkills = collect($skillEntries)->sortByDesc(fn ($s) => match ($s['status']) {
            'demonstrated' => 3, 'partial' => 2, 'claimed' => 1, default => 0,
        })->take(4);

        foreach ($topSkills as $s) {
            $explanations[] = "{$s['name']}: {$s['note']}";
        }
        if ($missingRequired) {
            $explanations[] = 'Gaps: ' . implode(', ', array_slice($missingRequired, 0, 4)) . (count($missingRequired) > 4 ? '…' : '');
        }
        if ($reqYears !== null) {
            $explanations[] = $candYears
                ? "Experience: {$candYears} yrs vs {$reqYears} required"
                : "Experience: none detected vs {$reqYears} required";
        }
        sort($signals);

        return [
            'score' => (int) round($total),
            'breakdown' => [
                'skills' => $skillsScore,
                'experience' => $experienceScore,
                'education' => $eduScore,
                'potential' => $potentialScore,
                'max' => array_sum($weights),
            ],
            'skill_evidence' => $skillEntries,
            'signals' => $signals,
            'explanations' => array_values(array_unique($explanations)),
            'requirements' => $reqs,
            'computed_at' => now()->toIso8601String(),
            'engine' => 'secondlook-rules-v1',
        ];
    }

    /** Score a candidate against every open job. */
    public function scoreAllJobs(Candidate $candidate): Collection
    {
        return Job::open()->get()->map(function (Job $job) use ($candidate) {
            $m = $this->score($candidate, $job);
            return [
                'job_id' => $job->id,
                'title' => $job->title,
                'client' => $job->client_name,
                'deadline' => $job->deadline?->toDateString(),
                'score' => $m['score'],
                'signals' => $m['signals'],
                'top_reasons' => array_slice($m['explanations'], 0, 3),
            ];
        })->sortByDesc('score')->values();
    }

    /** Persist the score onto an application. */
    public function scoreApplication(Application $application): Application
    {
        $m = $this->score($application->candidate, $application->job);

        // cross-vacancy intelligence: applied here, but better fit elsewhere?
        $others = $this->scoreAllJobs($application->candidate)
            ->first(fn ($r) => $r['job_id'] !== $application->job_id && $r['score'] >= $m['score'] + 10);
        if ($others) {
            $m['signals'][] = 'better_fit_elsewhere';
            $m['explanations'][] = "Strong match for \"{$others['title']}\" ({$others['score']}) - consider suggesting it to the candidate";
            $m['better_fit'] = $others;
        }

        $application->update([
            'ai_score' => $m['score'],
            'match_snapshot' => $m,
        ]);

        return $application->fresh();
    }

    /** Score every application that lacks a score, plus refresh stale ones. */
    public function refreshAll(?int $daysOld = 7): int
    {
        $count = 0;
        Application::with(['candidate.skills.skill', 'job'])->where(function ($q) use ($daysOld) {
            $q->whereNull('ai_score')->orWhere('updated_at', '<', now()->subDays($daysOld));
        })->chunk(50, function ($apps) use (&$count) {
            foreach ($apps as $app) {
                $this->scoreApplication($app);
                $count++;
            }
        });
        return $count;
    }

    /**
     * Learning loop - recruiters' final decisions nudge the weights.
     * Honest and inspectable: we measure whether approved candidates
     * leaned on demonstrated vs claimed evidence, then adjust slightly.
     */
    public function learnFromDecision(string $action, array $matchSnapshot): void
    {
        $approved = in_array($action, ['shortlist', 'advance', 'interview', 'offer', 'hired'], true);
        $rejected = in_array($action, ['reject', 'reject_ai'], true);
        if (! $approved && ! $rejected) {
            return;
        }

        $demonstrated = collect($matchSnapshot['skill_evidence'] ?? [])
            ->where('status', 'demonstrated')->count();
        $claimed = collect($matchSnapshot['skill_evidence'] ?? [])
            ->where('status', 'claimed')->count();

        $stats = SystemSetting::get('decision_stats', ['approved_dem' => 0, 'approved_claim' => 0, 'rejected_dem' => 0, 'rejected_claim' => 0]);

        if ($approved) {
            $demonstrated >= $claimed ? $stats['approved_dem']++ : $stats['approved_claim']++;
        } else {
            $demonstrated >= $claimed ? $stats['rejected_dem']++ : $stats['rejected_claim']++;
        }
        SystemSetting::put('decision_stats', $stats);

        // nudge: if approvals consistently favour demonstrated evidence, boost potential/skills slightly
        $weights = SystemSetting::get('match_weights', []);
        $demFavoursApproval = ($stats['approved_dem'] + 1) / max(1, $stats['approved_dem'] + $stats['approved_claim'] + 1);
        $weights['potential'] = round(($demFavoursApproval - 0.5) * 8, 2); // -4..+4
        SystemSetting::put('match_weights', $weights);
    }
}
