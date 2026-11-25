<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssessmentQuestion;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\AssessmentService;
use App\Services\CvParser;
use App\Services\MatchEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    public function jobs()
    {
        return Job::open()->get()->map(fn (Job $j) => [
            'id' => $j->id,
            'title' => $j->title,
            'client' => $j->client_name,
            'location' => $j->location,
            'type' => $j->employment_type,
            'salary' => $j->salary_min && $j->salary_max ? [$j->salary_min, $j->salary_max] : null,
            'deadline' => $j->deadline?->toDateString(),
            'summary' => $j->parsed_requirements['summary'] ?? null,
        ]);
    }

    public function job(Job $job)
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'client' => $job->client_name,
            'location' => $job->location,
            'type' => $job->employment_type,
            'salary' => $job->salary_min && $job->salary_max ? [$job->salary_min, $job->salary_max] : null,
            'deadline' => $job->deadline?->toDateString(),
            'description' => $job->description,
            'requirements' => $job->parsed_requirements,
        ];
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'cv_text' => 'required|string|min:50',
        ]);

        $candidate = Candidate::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'cv_text' => $data['cv_text'],
                'portal_token' => Str::random(40),
                'status' => 'active',
            ]
        );

        // parse the CV into claimed skills + profile flags
        $parsed = app(CvParser::class)->parse($data['cv_text']);
        foreach ($parsed['skills'] as $s) {
            $candidate->skills()->updateOrCreate(
                ['skill_id' => $s['skill_id']],
                [
                    'claimed_years' => $s['years'],
                    'claimed_evidence' => $s['evidence'],
                    'source' => 'CV',
                ]
            );
        }
        $candidate->update(['profile_flags' => array_merge($parsed['flags'] ?? [], [
            'years_experience' => $parsed['years_experience'],
            'education' => $parsed['education'],
        ])]);

        $application = Application::updateOrCreate(
            ['candidate_id' => $candidate->id, 'job_id' => $data['job_id']],
            ['status' => 'applied']
        );
        $application = app(MatchEngine::class)->scoreApplication($application);
        app(\App\Services\RediscoveryService::class)->forApplication($application);
        $candidate->notify('application', "Application received: {$application->job->title}", 'We will keep you updated here at every stage. No need to call.', '/applications');

        return response()->json([
            'message' => 'Application received - you will be kept informed',
            'portal_url' => url("/portal/{$candidate->portal_token}"),
            'token' => $candidate->portal_token,
            'initial_match' => [
                'score' => $application->ai_score,
                'signals' => $application->match_snapshot['signals'] ?? [],
            ],
        ], 201);
    }

    public function portal(string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();

        $skills = $candidate->skills()->with('skill')->get();
        $applications = $candidate->applications()->with('job')->get()->map(fn (Application $a) => [
            'job' => $a->job->title,
            'client' => $a->job->client_name,
            'status' => $a->status,
            'score' => $a->ai_score,
            'explanations' => array_slice($a->match_snapshot['explanations'] ?? [], 0, 5),
            'signals' => $a->match_snapshot['signals'] ?? [],
            'better_fit' => $a->match_snapshot['better_fit'] ?? null,
        ]);

        $suggested = app(MatchEngine::class)->scoreAllJobs($candidate)->take(4);

        return response()->json([
            'candidate' => [
                'name' => $candidate->name,
                'email' => $candidate->email,
                'headline' => $candidate->headline,
                'status' => $candidate->status,
            ],
            'skills' => [
                'claimed' => $skills->filter(fn ($s) => $s->claimed_evidence || $s->claimed_years)->map(fn ($s) => [
                    'name' => $s->skill?->name,
                    'years' => $s->claimed_years,
                    'evidence' => $s->claimed_evidence,
                ])->values(),
                'demonstrated' => $skills->whereNotNull('demonstrated_score')->map(fn ($s) => [
                    'name' => $s->skill?->name,
                    'score' => $s->demonstrated_score,
                ])->values(),
            ],
            'applications' => $applications,
            'suggested_jobs' => $suggested,
            'unread_notifications' => $candidate->notifications()->whereNull('read_at')->count(),
            'open_invitations' => $candidate->rediscoveries()->where('status', 'invited')->count(),
        ]);
    }

    public function assessmentQuestions(string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();
        return response()->json(app(AssessmentService::class)->questionsFor($candidate, 12));
    }

    public function submitAssessment(Request $request, string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:assessment_questions,id',
            'answers.*.answer_index' => 'required|integer',
            'job_id' => 'nullable|exists:jobs,id',
        ]);

        $attempt = app(AssessmentService::class)->submit($candidate, $data['job_id'] ?? null, $data['answers']);
        app(MatchEngine::class)->refreshAll(0);
        app(\App\Services\RediscoveryService::class)->scanAll();
        $candidate->notify('assessment', 'Skills check completed', "You scored {$attempt->overall_score}. Your proven skills now count in every match.", '/skills');

        return response()->json([
            'attempt_id' => $attempt->id,
            'overall_score' => $attempt->overall_score,
            'skill_scores' => $attempt->skill_scores,
            'message' => 'Assessment recorded - your profile was updated and matches recalculated',
        ]);
    }

    /** Rule-based help assistant - handles the routine inquiries that flood the phone lines. */
    public function help(Request $request)
    {
        $q = mb_strtolower((string) $request->input('q', ''));
        $answer = match (true) {
            str_contains($q, 'status') || str_contains($q, 'application') => 'You can see every application status in your portal. Applied -> screening -> shortlisted -> interview -> offer. You will hear from us at each step - no need to call.',
            str_contains($q, 'assess') || str_contains($q, 'test') => 'The skills check is a short set of scenario questions. It shows us what you can actually do - it is not about your CV keywords.',
            str_contains($q, 'interview') => 'If you are shortlisted, we schedule an interview and you will see the details in your portal with time, location and what to prepare.',
            str_contains($q, 'cv') || str_contains($q, 'resume') => 'Paste your CV when you apply. We read it, but our skills check and match analysis matter as much as keywords - weak CV formatting will not disqualify you.',
            str_contains($q, 'deadline') => 'Each vacancy shows its deadline. Applying before it closes is best, but we keep strong profiles on file for future roles.',
            str_contains($q, 'salary') => 'Salary ranges are shown on each vacancy card.',
            str_contains($q, 'contact') || str_contains($q, 'call') || str_contains($q, 'phone') => 'Our portal answers most questions instantly. For urgent matters our recruitment team is on +255 ... (placeholder).',
            default => 'I can help with application status, skills checks, interviews, CV questions, deadlines, and how the matching works. Try asking one of those.',
        };

        return response()->json(['answer' => $answer]);
    }
}
