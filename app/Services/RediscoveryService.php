<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\RediscoveryRecommendation;

/**
 * Talent Rediscovery Engine. When a candidate is not a strong match for the
 * role they applied for, search every other open vacancy. Matches go to a
 * recruiter as "pending review" - nothing reaches the candidate and nothing is
 * applied for on their behalf until a human approves it.
 */
class RediscoveryService
{
    public const WEAK_MATCH = 60;   // below this the original application is "not a strong match"
    public const MIN_ALT = 45;      // an alternative must reach at least this
    public const MIN_GAIN = 8;      // and beat the original by this many points

    public function __construct(private MatchEngine $engine)
    {
    }

    public function forApplication(Application $application): ?RediscoveryRecommendation
    {
        $candidate = $application->candidate;
        if (! $candidate || $candidate->consent_rediscovery === false) {
            return null;
        }
        $original = (int) ($application->ai_score ?? 0);
        if ($original >= self::WEAK_MATCH) {
            return null;
        }

        $appliedJobIds = $candidate->applications()->pluck('job_id')->all();
        $best = $this->engine->scoreAllJobs($candidate)
            ->reject(fn ($r) => in_array($r['job_id'], $appliedJobIds, true))
            ->first(fn ($r) => $r['score'] >= self::MIN_ALT && $r['score'] >= $original + self::MIN_GAIN);

        if (! $best) {
            return null;
        }

        $full = $this->engine->score($candidate, \App\Models\Job::find($best['job_id']));
        $gaps = collect($full['skill_evidence'] ?? [])
            ->filter(fn ($e) => ($e['status'] ?? null) === 'missing')
            ->pluck('name')->filter()->take(4)->values()->all();

        $rec = RediscoveryRecommendation::firstOrNew([
            'candidate_id' => $candidate->id,
            'job_id' => $best['job_id'],
        ]);
        if ($rec->exists && $rec->status !== 'pending_review') {
            return $rec; // a human already acted, never overwrite that
        }
        $rec->fill([
            'application_id' => $application->id,
            'score' => $best['score'],
            'original_score' => $original,
            'reasons' => array_slice($full['explanations'] ?? $best['top_reasons'], 0, 5),
            'gaps' => $gaps,
            'status' => 'pending_review',
        ])->save();

        return $rec;
    }

    public function scanAll(): int
    {
        $n = 0;
        Application::with(['candidate', 'job'])->get()->each(function (Application $a) use (&$n) {
            if ($this->forApplication($a)) {
                $n++;
            }
        });

        return $n;
    }

    public function invite(RediscoveryRecommendation $rec, string $recruiter = 'admin'): RediscoveryRecommendation
    {
        $rec->update(['status' => 'invited', 'reviewed_by' => $recruiter, 'reviewed_at' => now()]);
        $job = $rec->job;
        $rec->candidate->notify(
            'rediscovery',
            "You could be a strong fit for {$job->title}",
            "A Donswift recruiter reviewed your profile and would like to consider you for {$job->title} at {$job->client_name}. You can accept or decline. We will never apply on your behalf.",
            '/opportunities'
        );
        $rec->candidate->decisions()->create([
            'application_id' => $rec->application_id, 'job_id' => $rec->job_id,
            'action' => 'rediscovery_invited', 'reason' => 'Recruiter approved alternative opportunity', 'recruiter' => $recruiter,
        ]);

        return $rec->fresh();
    }

    public function dismiss(RediscoveryRecommendation $rec, string $recruiter = 'admin'): RediscoveryRecommendation
    {
        $rec->update(['status' => 'dismissed', 'reviewed_by' => $recruiter, 'reviewed_at' => now()]);

        return $rec->fresh();
    }

    public function respond(RediscoveryRecommendation $rec, bool $accept): RediscoveryRecommendation
    {
        $rec->update(['status' => $accept ? 'accepted' : 'declined', 'responded_at' => now()]);
        if ($accept) {
            // a NEW application with its own status; the original application is untouched
            $app = Application::firstOrCreate(
                ['candidate_id' => $rec->candidate_id, 'job_id' => $rec->job_id],
                ['status' => 'screening']
            );
            $this->engine->scoreApplication($app);
            $rec->candidate->notify('application', "You are now being considered for {$rec->job->title}", 'Track it under My applications.', '/applications');
        }

        return $rec->fresh();
    }
}
