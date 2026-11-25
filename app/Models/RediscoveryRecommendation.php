<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RediscoveryRecommendation extends Model
{
    public const STATUSES = [
        'pending_review' => 'Awaiting recruiter review',
        'invited' => 'Candidate invited',
        'dismissed' => 'Dismissed by recruiter',
        'accepted' => 'Candidate accepted',
        'declined' => 'Candidate declined',
    ];

    protected $fillable = [
        'candidate_id', 'application_id', 'job_id', 'score', 'original_score',
        'reasons', 'gaps', 'status', 'reviewed_by', 'reviewed_at', 'responded_at',
    ];

    protected $casts = [
        'reasons' => 'array',
        'gaps' => 'array',
        'reviewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
