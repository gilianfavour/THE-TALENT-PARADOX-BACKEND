<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Skill;

/**
 * Translates a vacancy into measurable requirements: which skills matter,
 * how much (required vs preferred), plus years/education expectations.
 */
class JobParser
{
    public function parse(Job $job): array
    {
        $text = $job->requirements_text ?: $job->description ?: '';
        $lower = mb_strtolower($text);

        // split into "must have" vs "nice to have" zones when section markers exist
        [$requiredZone, $preferredZone] = $this->splitZones($lower);

        $required = [];
        $preferred = [];

        foreach (Skill::all() as $skill) {
            $terms = array_merge([$skill->name], $skill->aliases ?? []);
            $inRequired = $this->mentionsAny($requiredZone, $terms);
            $inPreferred = $this->mentionsAny($preferredZone, $terms);
            // if no zones detected, everything is judged on the full text as required-or-preferred by proximity
            if (! $inRequired && ! $inPreferred) {
                $inPreferred = $this->mentionsAny($lower, $terms);
            }

            if ($inRequired) {
                $required[] = ['skill_id' => $skill->id, 'name' => $skill->name, 'category' => $skill->category, 'importance' => 'required'];
            } elseif ($inPreferred) {
                $preferred[] = ['skill_id' => $skill->id, 'name' => $skill->name, 'category' => $skill->category, 'importance' => 'preferred'];
            }
        }

        // soft skills get a floor: jobs almost always need communication/teamwork
        $soft = array_values(array_filter($preferred, fn ($s) => in_array($s['category'], ['Soft Skills'], true)));

        return [
            'required_skills' => $required,
            'preferred_skills' => $preferred,
            'years_min' => $this->detectYears($lower),
            'education_min' => $this->detectEducation($lower),
            'summary' => $this->summarize($job->title, $required, $preferred),
        ];
    }

    /** Returns [requiredZoneText, preferredZoneText]. If no markers, both fall back sensibly. */
    private function splitZones(string $lower): array
    {
        $requiredMarkers = ['must have', 'requirements', 'essential', 'you will need', 'minimum', 'qualifications', 'what we\'re looking for', 'who you are', 'skills and experience', 'responsibilities require'];
        $preferredMarkers = ['nice to have', 'desirable', 'preferred', 'bonus', 'advantageous', 'would be a plus'];

        $reqPos = null;
        $prefPos = null;
        foreach ($requiredMarkers as $m) {
            $p = mb_strpos($lower, $m);
            if ($p !== false && ($reqPos === null || $p < $reqPos)) {
                $reqPos = $p;
            }
        }
        foreach ($preferredMarkers as $m) {
            $p = mb_strpos($lower, $m);
            if ($p !== false && ($prefPos === null || $p < $prefPos)) {
                $prefPos = $p;
            }
        }

        if ($reqPos !== null && $prefPos !== null && $prefPos > $reqPos) {
            return [mb_substr($lower, $reqPos, $prefPos - $reqPos), mb_substr($lower, $prefPos)];
        }
        if ($reqPos !== null) {
            return [mb_substr($lower, $reqPos), ''];
        }
        if ($prefPos !== null) {
            return [mb_substr($lower, 0, $prefPos), mb_substr($lower, $prefPos)];
        }
        return [$lower, ''];
    }

    private function mentionsAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            $term = mb_strtolower(trim($term));
            if ($term === '') {
                continue;
            }
            if (preg_match('/(?<![a-z0-9])' . preg_quote($term, '/') . '(?![a-z0-9])/u', $text)) {
                return true;
            }
        }
        return false;
    }

    private function detectYears(string $lower): ?int
    {
        if (preg_match('/(\d{1,2})\s*\+?\s*years?/u', $lower, $m)) {
            return min((int) $m[1], 20);
        }
        if (preg_match('/entry[-\s]?level|no experience|graduate/u', $lower)) {
            return 0;
        }
        return null;
    }

    private function detectEducation(string $lower): ?string
    {
        if (preg_match('/ph\.?d|doctorate/u', $lower)) return 'PhD';
        if (preg_match('/master[\'’]?s?|mba/u', $lower)) return "Master's";
        if (preg_match('/bachelor[\'’]?s?|degree/u', $lower)) return "Bachelor's";
        if (preg_match('/diploma/u', $lower)) return 'Diploma';
        if (preg_match('/certificate/u', $lower)) return 'Certificate';
        if (preg_match('/secondary/u', $lower)) return 'Secondary';
        return null;
    }

    private function summarize(string $title, array $required, array $preferred): string
    {
        $names = array_map(fn ($s) => $s['name'], array_slice($required, 0, 6));
        $base = count($required) . ' required competencies';
        if ($names) {
            $base .= ' (' . implode(', ', $names) . (count($required) > 6 ? ', …' : '') . ')';
        }
        if ($preferred) {
            $base .= ' · ' . count($preferred) . ' preferred';
        }
        return $title . ': ' . $base;
    }
}
