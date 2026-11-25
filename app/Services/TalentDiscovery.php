<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\Experience;

/**
 * Talent Discovery Engine: reads every kind of experience (academic projects,
 * family business, volunteering, self-learning) and records the skills it
 * suggests as AI-inferred, never as verified. Evidence links upgrade a skill
 * to evidence-supported. Only an assessment or a recruiter can go higher.
 */
class TalentDiscovery
{
    public function __construct(private CvParser $parser)
    {
    }

    public function addExperience(Candidate $candidate, array $data): Experience
    {
        $experience = $candidate->experiences()->create($data);
        $text = trim(implode(' ', array_filter([$data['title'] ?? '', $data['organisation'] ?? '', $data['description'] ?? ''])));
        $parsed = $this->parser->parse($text . ' ' . $text); // parser needs some length; repeat is harmless for matching

        $inferred = [];
        $hasEvidence = ! empty($data['evidence_url']);
        foreach ($parsed['skills'] as $s) {
            $inferred[] = $s['name'];
            /** @var CandidateSkill $cs */
            $cs = $candidate->skills()->firstOrNew(['skill_id' => $s['skill_id']]);
            $current = $cs->exists ? $cs->evidenceLevel() : null;
            $target = $hasEvidence ? 'evidence_supported' : 'ai_inferred';
            if (! $current || $this->rank($target) > $this->rank($current)) {
                $cs->evidence_status = $target;
                $cs->source = Experience::TYPES[$data['type']] ?? $data['type'];
            }
            $cs->claimed_evidence ??= mb_substr($text, 0, 240);
            $cs->save();
        }

        $experience->update(['inferred_skills' => $inferred]);

        return $experience->fresh();
    }

    public function rank(string $level): int
    {
        return array_search($level, array_keys(CandidateSkill::LEVELS), true) ?: 0;
    }

    /** Full Talent Profile payload for the candidate portal. */
    public function profile(Candidate $candidate): array
    {
        $skills = $candidate->skills()->with('skill')->get()->map(fn (CandidateSkill $s) => [
            'id' => $s->id,
            'name' => $s->skill?->name,
            'category' => $s->skill?->category,
            'level' => $s->evidenceLevel(),
            'level_label' => CandidateSkill::LEVELS[$s->evidenceLevel()],
            'years' => $s->claimed_years,
            'score' => $s->demonstrated_score,
            'source' => $s->source ?? ($s->claimed_evidence ? 'CV' : null),
        ])->sortByDesc(fn ($s) => $this->rank($s['level']) * 1000 + ($s['score'] ?? 0))->values();

        $fields = ['name', 'email', 'phone', 'location', 'headline', 'career_interests'];
        $filled = collect($fields)->filter(fn ($f) => filled($candidate->{$f}))->count();
        $completion = (int) round(
            ($filled / count($fields)) * 40
            + min($candidate->experiences()->count(), 3) / 3 * 30
            + min($skills->count(), 6) / 6 * 15
            + ($skills->contains(fn ($s) => $s['score'] !== null) ? 15 : 0)
        );

        return [
            'candidate' => [
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'location' => $candidate->location,
                'headline' => $candidate->headline,
                'career_interests' => $candidate->career_interests,
                'talent_pool_visible' => (bool) ($candidate->talent_pool_visible ?? true),
                'consent_rediscovery' => (bool) ($candidate->consent_rediscovery ?? true),
                'flags' => $candidate->profile_flags ?? [],
            ],
            'completion' => min(100, $completion),
            'skills' => $skills,
            'levels' => CandidateSkill::LEVELS,
            'experiences' => $candidate->experiences()->get()->map(fn (Experience $e) => [
                'id' => $e->id,
                'type' => $e->type,
                'type_label' => Experience::TYPES[$e->type] ?? $e->type,
                'title' => $e->title,
                'organisation' => $e->organisation,
                'description' => $e->description,
                'evidence_url' => $e->evidence_url,
                'started_on' => $e->started_on,
                'ended_on' => $e->ended_on,
                'inferred_skills' => $e->inferred_skills ?? [],
            ]),
            'experience_types' => Experience::TYPES,
        ];
    }
}
