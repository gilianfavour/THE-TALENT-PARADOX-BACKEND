<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $fillable = [
        'title', 'client_name', 'location', 'employment_type',
        'salary_min', 'salary_max', 'deadline', 'status',
        'description', 'requirements_text', 'parsed_requirements',
    ];

    protected $casts = [
        'deadline' => 'date',
        'parsed_requirements' => 'array',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
