<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Candidate;

/**
 * Short skills checks: scenario-based MCQs mapped to taxonomy skills.
 * A correct answer *demonstrates* a skill - this is the evidence layer
 * that lets the engine see ability the CV can't express.
 */
class AssessmentService
{
    /** Questions for a candidate - prioritises claimed-but-unproven skills. */
    public function questionsFor(Candidate $candidate, int $limit = 12): array
    {
        $unprovenIds = $candidate->skills()
            ->whereNotNull('claimed_evidence')
            ->whereNull('demonstrated_score')
            ->pluck('skill_id');

        $unproven = AssessmentQuestion::whereIn('skill_id', $unprovenIds->isNotEmpty() ? $unprovenIds : [0])
            ->inRandomOrder()->limit((int) ceil($limit / 2))->get();

        $rest = AssessmentQuestion::whereNotIn('id', $unproven->pluck('id'))
            ->inRandomOrder()->limit($limit - $unproven->count())->get();

        return $unproven->concat($rest)->shuffle()->map(fn ($q) => [
            'id' => $q->id,
            'skill_id' => $q->skill_id,
            'skill' => $q->skill?->name,
            'question' => $q->question,
            'options' => $q->options,
        ])->values()->all();
    }

    /** Grade submitted answers, persist demonstrated scores, return results. */
    public function submit(Candidate $candidate, ?int $jobId, array $answers): AssessmentAttempt
    {
        $skillTotals = [];   // skill_id => [correct, total, weight]
        $graded = [];
        $correctCount = 0;

        foreach ($answers as $a) {
            $q = AssessmentQuestion::with('skill')->find($a['question_id'] ?? null);
            if (! $q) {
                continue;
            }
            $picked = (int) ($a['answer_index'] ?? -1);
            $correct = $picked === (int) $q->correct_index;
            if ($correct) {
                $correctCount++;
            }
            $w = max(1, (int) $q->difficulty);
            $skillTotals[$q->skill_id]['correct'] = ($skillTotals[$q->skill_id]['correct'] ?? 0) + ($correct ? $w : 0);
            $skillTotals[$q->skill_id]['total'] = ($skillTotals[$q->skill_id]['total'] ?? 0) + $w;
            $graded[] = [
                'question_id' => $q->id,
                'skill_id' => $q->skill_id,
                'skill' => $q->skill?->name,
                'picked' => $picked,
                'correct_index' => (int) $q->correct_index,
                'correct' => $correct,
            ];
        }

        // per-skill demonstrated score: 100 * weighted accuracy, floored at partial credit
        $skillScores = [];
        foreach ($skillTotals as $skillId => $t) {
            $score = (int) round(100 * $t['correct'] / max(1, $t['total']));
            $skillScores[$skillId] = $score;

            $candidate->skills()->updateOrCreate(
                ['skill_id' => $skillId],
                [
                    'demonstrated_score' => $score,
                    'demonstrated_at' => now(),
                ]
            );
        }

        $total = count($graded);
        $overall = $total ? (int) round(100 * $correctCount / $total) : 0;

        return AssessmentAttempt::create([
            'candidate_id' => $candidate->id,
            'job_id' => $jobId,
            'overall_score' => $overall,
            'skill_scores' => $skillScores,
            'answers' => $graded,
            'completed_at' => now(),
        ]);
    }
}
