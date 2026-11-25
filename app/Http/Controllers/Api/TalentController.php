<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Experience;
use App\Models\Job;
use App\Models\RediscoveryRecommendation;
use App\Services\MatchEngine;
use App\Services\RediscoveryService;
use App\Services\TalentDiscovery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Candidate-side Talent Discovery, Rediscovery and Notification endpoints.
 * Every route is scoped to the candidate's private portal token.
 */
class TalentController extends Controller
{
    private function candidate(string $token): Candidate
    {
        return Candidate::where('portal_token', $token)->firstOrFail();
    }

    public function profile(string $token, TalentDiscovery $discovery)
    {
        return response()->json($discovery->profile($this->candidate($token)));
    }

    public function updateProfile(Request $request, string $token, TalentDiscovery $discovery)
    {
        $candidate = $this->candidate($token);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:255',
            'career_interests' => 'nullable|string|max:500',
            'talent_pool_visible' => 'sometimes|boolean',
            'consent_rediscovery' => 'sometimes|boolean',
        ]);
        $candidate->update($data);

        return response()->json($discovery->profile($candidate->fresh()));
    }

    public function addExperience(Request $request, string $token, TalentDiscovery $discovery)
    {
        $candidate = $this->candidate($token);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Experience::TYPES))],
            'title' => 'required|string|max:255',
            'organisation' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'evidence_url' => 'nullable|url|max:500',
            'started_on' => 'nullable|string|max:20',
            'ended_on' => 'nullable|string|max:20',
        ]);
        $experience = $discovery->addExperience($candidate, $data);
        app(MatchEngine::class)->refreshAll(0);

        return response()->json([
            'experience' => $experience,
            'message' => count($experience->inferred_skills ?? [])
                ? 'We found possible skills in this experience. They are marked AI-inferred until you prove them.'
                : 'Experience saved. Add more detail to help us spot your skills.',
            'profile' => $discovery->profile($candidate->fresh()),
        ], 201);
    }

    public function deleteExperience(string $token, int $experience)
    {
        $this->candidate($token)->experiences()->whereKey($experience)->delete();

        return response()->json(['ok' => true]);
    }

    public function applications(string $token)
    {
        $candidate = $this->candidate($token);

        return response()->json($candidate->applications()->with('job')->latest()->get()->map(fn (Application $a) => [
            'id' => $a->id,
            'job_id' => $a->job_id,
            'job' => $a->job->title,
            'client' => $a->job->client_name,
            'status' => $a->status,
            'status_label' => Application::STATUSES[$a->status] ?? ucfirst($a->status),
            'score' => $a->ai_score,
            'explanations' => $a->match_snapshot['explanations'] ?? [],
            'signals' => $a->match_snapshot['signals'] ?? [],
            'breakdown' => $a->match_snapshot['breakdown'] ?? null,
            'applied_at' => $a->created_at?->toDateString(),
            'updated_at' => $a->updated_at?->toDateString(),
        ]));
    }

    public function opportunities(string $token)
    {
        $candidate = $this->candidate($token);
        $invites = $candidate->rediscoveries()->with('job')
            ->whereIn('status', ['invited', 'accepted', 'declined'])->latest()->get()
            ->map(fn (RediscoveryRecommendation $r) => [
                'id' => $r->id,
                'job_id' => $r->job_id,
                'title' => $r->job->title,
                'client' => $r->job->client_name,
                'location' => $r->job->location,
                'deadline' => $r->job->deadline?->toDateString(),
                'score' => $r->score,
                'reasons' => $r->reasons ?? [],
                'gaps' => $r->gaps ?? [],
                'status' => $r->status,
            ]);

        $applied = $candidate->applications()->pluck('job_id')->all();
        $suggested = app(MatchEngine::class)->scoreAllJobs($candidate)
            ->reject(fn ($s) => in_array($s['job_id'], $applied, true))
            ->take(6)->values();

        return response()->json(['invitations' => $invites, 'suggested' => $suggested]);
    }

    public function respond(Request $request, string $token, int $recommendation, RediscoveryService $service)
    {
        $candidate = $this->candidate($token);
        $data = $request->validate(['decision' => 'required|in:accept,decline']);
        $rec = $candidate->rediscoveries()->whereKey($recommendation)->where('status', 'invited')->firstOrFail();
        $rec = $service->respond($rec, $data['decision'] === 'accept');

        return response()->json([
            'status' => $rec->status,
            'message' => $rec->status === 'accepted'
                ? 'Thank you. A recruiter will now consider you for this role.'
                : 'No problem. Your other applications are not affected.',
        ]);
    }

    public function notifications(string $token)
    {
        $candidate = $this->candidate($token);

        return response()->json([
            'unread' => $candidate->notifications()->whereNull('read_at')->count(),
            'items' => $candidate->notifications()->limit(50)->get(['id', 'type', 'title', 'body', 'link', 'read_at', 'created_at']),
        ]);
    }

    public function markRead(string $token)
    {
        $this->candidate($token)->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Public, non-personal numbers for the marketing site. */
    public function stats()
    {
        return response()->json([
            'open_vacancies' => Job::open()->count(),
            'candidates' => Candidate::count(),
            'skills_proven' => \App\Models\CandidateSkill::whereNotNull('demonstrated_score')->count(),
            'rediscovered' => RediscoveryRecommendation::count(),
            'ai_engine' => app(\App\Services\Ai\Claude::class)->label(),
        ]);
    }
}
