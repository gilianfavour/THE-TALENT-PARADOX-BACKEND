<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    public const TYPES = [
        'employment' => 'Formal employment',
        'informal' => 'Informal work or family business',
        'volunteering' => 'Volunteering',
        'internship' => 'Internship or industrial training',
        'academic_project' => 'Academic project',
        'personal_project' => 'Personal project',
        'freelance' => 'Freelancing',
        'self_learning' => 'Self-taught skill',
        'entrepreneurship' => 'Entrepreneurship',
        'community' => 'Community activity',
    ];

    protected $fillable = [
        'candidate_id', 'type', 'title', 'organisation', 'description',
        'evidence_url', 'started_on', 'ended_on', 'inferred_skills',
    ];

    protected $casts = ['inferred_skills' => 'array'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
