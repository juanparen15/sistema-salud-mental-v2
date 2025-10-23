<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SuicideAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'event_date',
        'week_number',
        'admission_via',
        'attempt_number',
        'benefit_plan',
        'trigger_factor',
        'risk_factors',
        'mechanism',
        'additional_observation',
        'status',
        // 'created_by',
        'created_by_id',
        'assigned_to',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'risk_factors' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followups(): MorphMany
    {
        return $this->morphMany(MonthlyFollowup::class, 'followupable');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
