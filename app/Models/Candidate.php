<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'location', 'portal_token', 'headline',
        'cv_text', 'profile_flags', 'status', 'career_interests',
        'talent_pool_visible', 'consent_rediscovery', 'password',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'profile_flags' => 'array',
        'talent_pool_visible' => 'boolean',
        'consent_rediscovery' => 'boolean',
    ];

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

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->latest();
    }

    public function rediscoveries(): HasMany
    {
        return $this->hasMany(RediscoveryRecommendation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CandidateNotification::class)->latest();
    }

    public function notify(string $type, string $title, ?string $body = null, ?string $link = null): CandidateNotification
    {
        return $this->notifications()->create(compact('type', 'title', 'body', 'link'));
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
