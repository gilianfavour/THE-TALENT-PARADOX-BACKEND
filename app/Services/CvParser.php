<?php

namespace App\Services;

use App\Models\Skill;

/**
 * Extracts *claimed* skills and profile signals from raw CV text.
 * Everything here is deterministic - a skill is "claimed" only when
 * the CV literally evidences it. What the CV cannot show (real ability)
 * is what assessments are for.
 */
class CvParser
{
    /**
     * @return array{skills: array<int, array{skill_id:int,name:string,years:?int,evidence:string,category:string}>,
     *               flags: array<string,mixed>, years_experience:?int, education:?string}
     */
    public function parse(string $cvText): array
    {
        $cv = mb_strtolower($cvText);
        $found = [];

        foreach (Skill::all() as $skill) {
            $terms = array_merge([$skill->name], $skill->aliases ?? []);
            $hit = null;

            foreach ($terms as $term) {
                $term = mb_strtolower(trim($term));
                if ($term === '') {
                    continue;
                }
                // word-boundary-ish matching; allow +.# inside terms
                $pattern = '/(?<![a-z0-9])' . preg_quote($term, '/') . '(?![a-z0-9])/u';
                if (preg_match($pattern, $cv)) {
                    $hit = $term;
                    break;
                }
            }

            if ($hit === null) {
                continue;
            }

            // pull the sentence/line containing the match as evidence
            $evidence = $this->extractEvidence($cvText, $hit);
            $years = $this->extractYearsNear($cv, $hit);

            $found[] = [
                'skill_id' => $skill->id,
                'name' => $skill->name,
                'years' => $years,
                'evidence' => $evidence,
                'category' => $skill->category,
            ];
        }

        return [
            'skills' => $found,
            'flags' => $this->detectFlags($cv),
            'years_experience' => $this->detectYearsExperience($cv),
            'education' => $this->detectEducation($cv),
        ];
    }

    private function extractEvidence(string $cvText, string $term): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $cvText);
        $needle = mb_strtolower($term);
        foreach ($lines as $line) {
            if (str_contains(mb_strtolower($line), $needle)) {
                $line = trim(preg_replace('/\s+/', ' ', $line));
                return mb_strlen($line) > 160 ? mb_substr($line, 0, 157) . '…' : $line;
            }
        }
        return '';
    }

    private function extractYearsNear(string $cv, string $term): ?int
    {
        $pos = mb_strpos($cv, $term);
        if ($pos === false) {
            return null;
        }
        $window = mb_substr($cv, max(0, $pos - 120), 240);
        if (preg_match('/(\d{1,2})\s*\+?\s*(?:years?|yrs?)/u', $window, $m)) {
            return min((int) $m[1], 40);
        }
        return null;
    }

    private function detectFlags(string $cv): array
    {
        $flags = [];

        if (preg_match('/career\s+(change|switch|transition|pivot)|changing\s+careers|transitioning\s+(into|to|from)|previous\s+career|career\s+break\s+into/u', $cv)) {
            $flags['career_changer'] = true;
        }
        if (preg_match('/self[-\s]?taught|self\s+learning|online\s+(courses|certificates)|udemy|freecodecamp|coursera|alison|learned\s+on\s+my\s+own/u', $cv)) {
            $flags['self_taught'] = true;
        }
        if (preg_match('/career\s+break|sabbatical|stay[-\s]?at[-\s]?home|time\s+out\s+of\s+(work|employment)|gap\s+in\s+(my\s+)?(work|employment)/u', $cv)) {
            $flags['employment_gap'] = true;
        }
        if (preg_match('/first\s+job|no\s+formal\s+experience|fresh\s+graduate|recent\s+graduate|entry[-\s]?level|junior/u', $cv)) {
            $flags['early_career'] = true;
        }

        return $flags;
    }

    private function detectYearsExperience(string $cv): ?int
    {
        $max = 0;
        if (preg_match_all('/(\d{1,2})\s*\+?\s*(?:years?|yrs?)\s+(?:of\s+)?(?:experience|work|professional)/u', $cv, $m)) {
            foreach ($m[1] as $y) {
                $max = max($max, (int) $y);
            }
        }
        // fallback: any "N years" mention
        if ($max === 0 && preg_match_all('/(\d{1,2})\s*\+?\s*years?/u', $cv, $m)) {
            foreach ($m[1] as $y) {
                $v = (int) $y;
                if ($v <= 30) {
                    $max = max($max, $v);
                }
            }
        }
        return $max > 0 ? min($max, 40) : null;
    }

    private function detectEducation(string $cv): ?string
    {
        if (preg_match('/ph\.?d|doctorate/u', $cv)) {
            return 'PhD';
        }
        if (preg_match('/master[\'’]?s?|msc|mba/u', $cv)) {
            return "Master's";
        }
        if (preg_match('/bachelor[\'’]?s?|bsc|ba\s|degree/u', $cv)) {
            return "Bachelor's";
        }
        if (preg_match('/diploma|higher\s+diploma/u', $cv)) {
            return 'Diploma';
        }
        if (preg_match('/certificate|certified|certification/u', $cv)) {
            return 'Certificate';
        }
        if (preg_match('/secondary\s+(school|education)|ordinary\s+level|o-?level|high\s+school/u', $cv)) {
            return 'Secondary';
        }
        return null;
    }
}
