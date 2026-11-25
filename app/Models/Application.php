<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public const STATUSES = [
        'applied' => 'Applied',
        'screening' => 'Screening',
        'shortlisted' => 'Shortlisted',
        'interview' => 'Interview',
        'offer' => 'Offer',
        'hired' => 'Hired',
        'rejected' => 'Not progressing',
    ];

    protected $fillable = [
        'candidate_id', 'job_id', 'status', 'ai_score', 'match_snapshot',
    ];

    protected $casts = ['match_snapshot' => 'array'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
