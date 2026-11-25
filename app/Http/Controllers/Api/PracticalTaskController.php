<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\MatchEngine;
use App\Services\PracticalAssessmentService;
use App\Services\PracticalTaskLibrary;
use Illuminate\Http\Request;

/** Candidate-side practical tasks, scoped to the private portal token. */
class PracticalTaskController extends Controller
{
    public function __construct(private PracticalTaskLibrary $library)
    {
    }

    private function candidate(string $token): Candidate
    {
        return Candidate::where('portal_token', $token)->firstOrFail();
    }

    private function latest(Candidate $candidate, string $slug): ?AssessmentAttempt
    {
        return $candidate->attempts()->where('answers->type', 'practical')->where('answers->task', $slug)->latest()->first();
    }

    private function result(?AssessmentAttempt $a): ?array
    {
        if (! $a) {
            return null;
        }
        $ans = $a->answers ?? [];

        return [
            'status' => $ans['status'] ?? 'needs_review',
            'preliminary_score' => collect($ans['criteria'] ?? [])->sum('points'),
            'final_score' => $ans['final_score'] ?? null,
            'criteria' => $ans['criteria'] ?? [],
            'review_reason' => $ans['review_reason'] ?? null,
            'reviewer' => $ans['reviewer'] ?? null,
            'review_note' => $ans['review_note'] ?? null,
            'submitted_at' => $a->completed_at?->toIso8601String(),
            'response' => $ans['response'] ?? '',
        ];
    }

    /** Tasks for the vacancies the candidate applied to, then their best-matching other vacancies. */
    public function index(string $token)
    {
        $candidate = $this->candidate($token);
        $applied = $candidate->applications()->with('job')->get()->pluck('job')->filter();
        $suggested = app(MatchEngine::class)->scoreAllJobs($candidate)->take(3)
            ->map(fn ($r) => Job::find($r['job_id']))->filter();

        $tasks = collect();
        foreach ([[$applied, 'applied'], [$suggested, 'suggested']] as [$jobs, $why]) {
            foreach ($jobs as $job) {
                $task = $this->library->forJob($job);
                if ($tasks->has($task['slug'])) {
                    continue;
                }
                $tasks->put($task['slug'], $this->library->publicView($task) + [
                    'job_id' => $job->id,
                    'job' => $job->title,
                    'why' => $why,
                    'result' => $this->result($this->latest($candidate, $task['slug'])),
                ]);
            }
        }

        return response()->json($tasks->values());
    }

    public function show(Request $request, string $token, string $slug)
    {
        $candidate = $this->candidate($token);
        $task = $this->library->find($slug);
        abort_unless($task, 404);
        $jobId = $request->integer('job') ?: null;

        return response()->json($this->library->publicView($task) + [
            'job_id' => $jobId,
            'job' => $jobId ? Job::find($jobId)?->title : null,
            'result' => $this->result($this->latest($candidate, $slug)),
        ]);
    }

    public function submit(Request $request, string $token, string $slug, PracticalAssessmentService $service)
    {
        $candidate = $this->candidate($token);
        $data = $request->validate([
            'response' => 'required|string|min:20|max:10000',
            'link' => 'nullable|url|max:500',
            'job_id' => 'nullable|exists:jobs,id',
        ]);
        $attempt = $service->submit($candidate, $slug, $data['job_id'] ?? null, $data['response'], $data['link'] ?? null);
        app(MatchEngine::class)->refreshAll(0);
        $candidate->notify('assessment', "Practical task submitted: {$attempt->answers['task_title']}", "Preliminary score {$attempt->overall_score}. A recruiter will review your work before it counts as validated.", '/assessments');

        return response()->json([
            'message' => 'Submitted. Your preliminary result is below and a recruiter will review it.',
            'result' => $this->result($attempt),
        ], 201);
    }
}
