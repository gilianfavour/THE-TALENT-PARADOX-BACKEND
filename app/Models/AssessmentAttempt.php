<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'candidate_id', 'job_id', 'overall_score', 'skill_scores', 'answers', 'completed_at',
    ];

    protected $casts = [
        'skill_scores' => 'array',
        'answers' => 'array',
        'completed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
