<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'location', 'portal_token', 'headline',
        'cv_text', 'profile_flags', 'status',
    ];

    protected $casts = ['profile_flags' => 'array'];

    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function claimedSkills()
    {
        return $this->skills()->whereNotNull('claimed_years')->orWhereNotNull('claimed_evidence')->with('skill');
    }

    public function demonstratedSkills()
    {
        return $this->skills()->whereNotNull('demonstrated_score')->with('skill');
    }
}
