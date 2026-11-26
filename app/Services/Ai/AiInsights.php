<?php

namespace App\Services\Ai;

use App\Models\Job;
use App\Models\Skill;
use Illuminate\Support\Collection;

/**
 * The AI intelligence layer (spec section 7). Every method returns structured data that
 * is validated against the skills taxonomy before anything is stored, or null so callers
 * fall back to the deterministic rules. AI suggests; recruiters and evidence decide.
 */
class AiInsights
{
    private ?Collection $skills = null;

    public function __construct(private Claude $claude)
    {
    }

    public function enabled(): bool
    {
        return $this->claude->enabled();
    }

    public function engine(): string
    {
        return $this->claude->enabled() ? 'claude:' . $this->claude->model() : 'rules';
    }

    private function taxonomy(): Collection
    {
        return $this->skills ??= Skill::all(['id', 'name', 'category', 'aliases']);
    }

    private function taxonomyList(): string
    {
        return $this->taxonomy()->groupBy('category')
            ->map(fn ($g, $cat) => $cat . ': ' . $g->pluck('name')->implode(', '))
            ->implode("\n");
    }

    /** Map a model-provided skill name onto the taxonomy (exact name or alias, case-insensitive). */
    public function resolve(string $name): ?Skill
    {
        $n = mb_strtolower(trim($name));

        return $this->taxonomy()->first(function (Skill $s) use ($n) {
            if (mb_strtolower($s->name) === $n) {
                return true;
            }

            return collect($s->aliases ?? [])->contains(fn ($a) => mb_strtolower($a) === $n);
        });
    }

    /**
     * Semantic skill discovery from free text (projects, informal work, volunteering, CVs).
     * "Coordinated 30 volunteers and tracked progress" -> Project Management, Organization, Leadership.
     *
     * @return array<int, array{skill_id:int, name:string, evidence:string, confidence:float}>|null
     */
    public function skillsFromText(string $text, string $context = 'experience'): ?array
    {
        if (! $this->enabled() || mb_strlen(trim($text)) < 15) {
            return null;
        }

        $data = $this->claude->json(
            'You are the Talent Discovery Engine for a Ugandan recruitment firm. You read how people describe their work, including informal work, family businesses, volunteering, school projects and self-taught skills, and identify professional competencies they POTENTIALLY have. Translate informal descriptions into professional competency names. Only use skills from the provided taxonomy. Never infer personality, honesty or emotional state. Be conservative: every skill must be supported by a short quote from the text.',
            "Taxonomy (use these exact names only):\n{$this->taxonomyList()}\n\nContext: {$context}\nText:\n\"\"\"\n" . mb_substr($text, 0, 6000) . "\n\"\"\"\n\nReturn JSON: {\"skills\":[{\"name\":\"<taxonomy name>\",\"evidence\":\"<short quote from the text>\",\"confidence\":0.0-1.0}]}. At most 10 skills, highest confidence first.",
            900
        );

        $out = [];
        foreach (($data['skills'] ?? []) as $s) {
            $skill = is_array($s) && isset($s['name']) ? $this->resolve((string) $s['name']) : null;
            $confidence = (float) ($s['confidence'] ?? 0.6);
            if (! $skill || $confidence < 0.35 || isset($out[$skill->id])) {
                continue;
            }
            $out[$skill->id] = [
                'skill_id' => $skill->id,
                'name' => $skill->name,
                'evidence' => mb_substr(trim((string) ($s['evidence'] ?? '')), 0, 240),
                'confidence' => round(min(1, $confidence), 2),
            ];
        }

        return $data === null ? null : array_values($out);
    }

    /**
     * Vacancy intelligence: competencies, behavioural expectations and practical job tasks
     * from a client job description. Mandatory items are only SUGGESTED; a recruiter confirms.
     */
    public function analyseJob(Job $job): ?array
    {
        if (! $this->enabled()) {
            return null;
        }
        $text = trim(($job->title ?? '') . "\n" . ($job->requirements_text ?: $job->description ?: ''));
        if (mb_strlen($text) < 30) {
            return null;
        }

        $data = $this->claude->json(
            'You are the Vacancy Intelligence engine for a recruitment firm. Convert a client job description into measurable, job-related requirements. Separate what the text says is required from what is only desirable. Never invent requirements that are not in the text. Do not include anything about age, gender, religion, tribe, marital status or other protected characteristics.',
            "Taxonomy (map competencies to these exact names only):\n{$this->taxonomyList()}\n\nJob description:\n\"\"\"\n" . mb_substr($text, 0, 6000) . "\n\"\"\"\n\nReturn JSON: {\"required\":[\"<taxonomy name>\"],\"preferred\":[\"<taxonomy name>\"],\"behavioural_expectations\":[\"<short phrase>\"],\"practical_tasks\":[\"<a realistic task a hire would do in week one>\"],\"mandatory_suggestions\":[\"<explicit, job-related eligibility condition from the text>\"],\"years_min\":<int or null>,\"summary\":\"<one sentence>\"}. Max 8 items per list.",
            1100
        );
        if ($data === null) {
            return null;
        }

        $map = function ($names, string $importance) {
            return collect(is_array($names) ? $names : [])
                ->map(fn ($n) => is_string($n) ? $this->resolve($n) : null)
                ->filter()->unique('id')
                ->map(fn (Skill $s) => ['skill_id' => $s->id, 'name' => $s->name, 'category' => $s->category, 'importance' => $importance])
                ->values()->all();
        };
        $strings = fn ($v) => collect(is_array($v) ? $v : [])->filter(fn ($x) => is_string($x) && $x !== '')->map(fn ($x) => mb_substr(trim($x), 0, 160))->take(8)->values()->all();

        return [
            'required_skills' => $map($data['required'] ?? [], 'required'),
            'preferred_skills' => $map($data['preferred'] ?? [], 'preferred'),
            'behavioural_expectations' => $strings($data['behavioural_expectations'] ?? []),
            'practical_tasks' => $strings($data['practical_tasks'] ?? []),
            'mandatory_suggestions' => $strings($data['mandatory_suggestions'] ?? []),
            'years_min' => is_numeric($data['years_min'] ?? null) ? (int) $data['years_min'] : null,
            'summary' => is_string($data['summary'] ?? null) ? mb_substr($data['summary'], 0, 300) : null,
        ];
    }

    /**
     * Assessment intelligence: grade a practical task answer against its rubric.
     * Returns per-criterion points (clamped to each weight) and feedback, or null.
     */
    public function gradeTask(array $task, string $response): ?array
    {
        if (! $this->enabled()) {
            return null;
        }
        $rubric = collect($task['rubric'])->map(fn ($c) => "- key \"{$c['key']}\": {$c['label']} (max {$c['weight']} points). Guidance: {$c['tip']}")->implode("\n");

        $data = $this->claude->json(
            'You are an assessment assistant for a recruitment firm. You grade a candidate\'s practical task answer strictly against the rubric. Judge only the work shown, not writing polish, grammar, accent or background. Never comment on honesty, character, personality or emotional state. Your score is preliminary; a human recruiter makes the final call.',
            "Task: {$task['title']}\nBrief: {$task['brief']}\nScenario:\n{$task['scenario']}\n\nRubric:\n{$rubric}\n\nCandidate answer:\n\"\"\"\n" . mb_substr($response, 0, 8000) . "\n\"\"\"\n\nReturn JSON: {\"criteria\":[{\"key\":\"<rubric key>\",\"points\":<number 0..max>,\"feedback\":\"<one or two sentences for the candidate>\"}],\"needs_human_attention\":\"<why a recruiter should look closely, or empty>\"}",
            1000
        );
        if ($data === null || ! is_array($data['criteria'] ?? null)) {
            return null;
        }

        $byKey = collect($data['criteria'])->filter(fn ($c) => is_array($c) && isset($c['key']))->keyBy('key');
        $criteria = [];
        foreach ($task['rubric'] as $c) {
            $g = $byKey->get($c['key']);
            if (! $g) {
                return null; // incomplete grading: fall back to the rubric check
            }
            $points = round(max(0, min($c['weight'], (float) ($g['points'] ?? 0))), 1);
            $ratio = $c['weight'] ? $points / $c['weight'] : 0;
            $criteria[] = [
                'key' => $c['key'],
                'label' => $c['label'],
                'weight' => $c['weight'],
                'points' => $points,
                'status' => $ratio >= 0.8 ? 'shown' : ($ratio >= 0.3 ? 'partly' : 'not_shown'),
                'feedback' => mb_substr(trim((string) ($g['feedback'] ?? $c['tip'])), 0, 400),
            ];
        }

        return [
            'criteria' => $criteria,
            'score' => (int) round(collect($criteria)->sum('points')),
            'attention' => is_string($data['needs_human_attention'] ?? null) ? mb_substr($data['needs_human_attention'], 0, 300) : '',
        ];
    }
}
