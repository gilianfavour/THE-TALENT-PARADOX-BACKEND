<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Candidate;
use App\Models\Skill;

/**
 * Scores practical task submissions against the task rubric to produce a PRELIMINARY
 * result with per-criterion feedback. Every practical submission goes to a recruiter for
 * review; only a recruiter can mark the skills as human-validated.
 */
class PracticalAssessmentService
{
    public function __construct(private PracticalTaskLibrary $library)
    {
    }

    public function evaluate(array $task, string $response): array
    {
        $text = mb_strtolower($response);
        $words = str_word_count(strip_tags($response));
        $lengthFactor = $words >= 40 ? 1.0 : ($words >= 20 ? 0.75 : 0.5);

        $criteria = [];
        $total = 0;
        foreach ($task['rubric'] as $c) {
            $hits = array_values(array_filter($c['signals'], fn ($s) => str_contains($text, mb_strtolower($s))));
            $ratio = min(1, count($hits) / max(1, $c['need']));
            $points = round($c['weight'] * $ratio * $lengthFactor, 1);
            $total += $points;
            $criteria[] = [
                'key' => $c['key'],
                'label' => $c['label'],
                'weight' => $c['weight'],
                'points' => $points,
                'status' => $ratio >= 1 ? 'shown' : ($ratio > 0 ? 'partly' : 'not_shown'),
                'feedback' => $ratio >= 1 ? 'Clearly demonstrated.' : $c['tip'],
            ];
        }

        $score = (int) round($total);
        $reasons = [];
        if ($score >= 40 && $score <= 75) {
            $reasons[] = 'Borderline preliminary score';
        }
        if ($words < 40) {
            $reasons[] = 'Short answer, may need context';
        }
        if (count(array_filter($criteria, fn ($c) => $c['status'] === 'partly')) >= 2) {
            $reasons[] = 'Several criteria partly met';
        }

        return [
            'score' => $score,
            'words' => $words,
            'criteria' => $criteria,
            'review_reason' => $reasons ? implode('; ', $reasons) : 'Standard human validation',
        ];
    }

    public function submit(Candidate $candidate, string $slug, ?int $jobId, string $response, ?string $link = null): AssessmentAttempt
    {
        $task = $this->library->find($slug);
        abort_unless($task, 404, 'Unknown task');

        $result = $this->evaluate($task, $response);
        $skillIds = Skill::whereIn('name', $task['skills'])->pluck('id', 'name');

        $skillScores = [];
        foreach ($task['skills'] as $name) {
            $id = $skillIds[$name] ?? null;
            if (! $id) {
                continue;
            }
            $skillScores[$id] = $result['score'];
            $cs = $candidate->skills()->firstOrNew(['skill_id' => $id]);
            if ($cs->validated_at) {
                continue; // never overwrite a recruiter-validated result with a machine score
            }
            $cs->demonstrated_score = $result['score'];
            $cs->demonstrated_at = now();
            $cs->source = 'Practical task: ' . $task['title'];
            $cs->save();
        }

        return AssessmentAttempt::create([
            'candidate_id' => $candidate->id,
            'job_id' => $jobId,
            'overall_score' => $result['score'],
            'skill_scores' => $skillScores,
            'answers' => [
                'type' => 'practical',
                'task' => $slug,
                'task_title' => $task['title'],
                'response' => $response,
                'link' => $link,
                'words' => $result['words'],
                'criteria' => $result['criteria'],
                'status' => 'needs_review',
                'review_reason' => $result['review_reason'],
            ],
            'completed_at' => now(),
        ]);
    }

    /** Recruiter confirms or adjusts the score. Skills become human-validated. */
    public function validate(AssessmentAttempt $attempt, int $finalScore, string $reviewer, ?string $note = null): AssessmentAttempt
    {
        $finalScore = max(0, min(100, $finalScore));
        $answers = $attempt->answers ?? [];
        $answers['status'] = 'validated';
        $answers['final_score'] = $finalScore;
        $answers['reviewer'] = $reviewer;
        $answers['review_note'] = $note;
        $answers['reviewed_at'] = now()->toIso8601String();

        $skillScores = [];
        foreach (array_keys($attempt->skill_scores ?? []) as $skillId) {
            $skillScores[$skillId] = $finalScore;
            $attempt->candidate->skills()->updateOrCreate(
                ['skill_id' => $skillId],
                [
                    'demonstrated_score' => $finalScore,
                    'demonstrated_at' => now(),
                    'validated_at' => now(),
                    'validated_by' => $reviewer,
                ]
            );
        }

        $attempt->update(['answers' => $answers, 'skill_scores' => $skillScores, 'overall_score' => $finalScore]);

        $attempt->candidate->notify(
            'assessment',
            "Your practical task was reviewed: {$answers['task_title']}",
            "A Donswift recruiter reviewed your work and confirmed a score of {$finalScore}. These skills now show as human-validated on your profile.",
            '/assessments'
        );
        $jobId = $attempt->job_id ?? $attempt->candidate->applications()->value('job_id');
        if ($jobId) {
            $attempt->candidate->decisions()->create([
                'application_id' => null,
                'job_id' => $jobId,
                'action' => 'practical_validated',
                'reason' => trim("Validated {$answers['task_title']} at {$finalScore}. " . ($note ?? '')),
                'recruiter' => $reviewer,
            ]);
        }
        app(MatchEngine::class)->refreshAll(0);

        return $attempt->fresh();
    }
}
