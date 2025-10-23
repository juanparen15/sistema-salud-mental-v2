<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MentalDisorder extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'admission_date',
        'admission_type',
        'admission_via',
        'service_area',
        'diagnosis_code',
        'diagnosis_description',
        'diagnosis_date',
        'diagnosis_type',
        'additional_observation',
        'status',
        // 'created_by',
        'created_by_id',
        'assigned_to',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'diagnosis_date' => 'datetime',
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

    public function getCurrentMonthFollowup()
    {
        return $this->followups()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();
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
