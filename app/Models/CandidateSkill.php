<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSkill extends Model
{
    protected $fillable = [
        'candidate_id', 'skill_id', 'claimed_years', 'claimed_evidence',
        'demonstrated_score', 'demonstrated_at', 'evidence_status', 'source',
        'validated_at', 'validated_by',
    ];

    public const LEVELS = [
        'self_reported' => 'Self-reported',
        'ai_inferred' => 'AI-inferred',
        'evidence_supported' => 'Evidence-supported',
        'assessed' => 'Assessed',
        'human_validated' => 'Human-validated',
    ];

    protected $casts = ['demonstrated_at' => 'datetime', 'validated_at' => 'datetime'];

    /** The strongest evidence we hold for this skill. */
    public function evidenceLevel(): string
    {
        if ($this->validated_at) {
            return 'human_validated';
        }
        if ($this->demonstrated_score !== null) {
            return 'assessed';
        }
        return $this->evidence_status ?: 'self_reported';
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
