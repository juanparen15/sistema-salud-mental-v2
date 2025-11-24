<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuicideAttempt extends Model
{
    use HasFactory, SoftDeletes;

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
        'created_by_id',
        'updated_by_id',
        // NOTA: 'assigned_to' NO existe en esta tabla, solo en patients
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'risk_factors' => 'array', // JSON en la BD
    ];

    // ==================== RELACIONES ====================

    /**
     * Paciente asociado al intento de suicidio
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Usuario que creó el registro
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Usuario que actualizó el registro
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Seguimientos mensuales (relación polimórfica)
     */
    public function followups(): MorphMany
    {
        return $this->morphMany(MonthlyFollowup::class, 'followupable');
    }

    // ==================== MÉTODOS HELPER ====================

    /**
     * Obtener seguimiento del mes actual
     */
    public function getCurrentMonthFollowup()
    {
        return $this->followups()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();
    }

    /**
     * Verificar si tiene seguimientos en un período
     */
    public function hasFollowupsInPeriod(int $year, int $month): bool
    {
        return $this->followups()
            ->where('year', $year)
            ->where('month', $month)
            ->exists();
    }

    /**
     * Obtener seguimientos por año
     */
    public function getFollowupsByYear(int $year)
    {
        return $this->followups()
            ->where('year', $year)
            ->orderBy('month')
            ->get();
    }

    /**
     * Verificar si está activo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verificar si está inactivo
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Verificar si fue resuelto
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Es primer intento
     */
    public function isFirstAttempt(): bool
    {
        return $this->attempt_number === 1;
    }

    /**
     * Obtener factores de riesgo como string
     */
    public function getRiskFactorsStringAttribute(): string
    {
        if (!$this->risk_factors || !is_array($this->risk_factors)) {
            return 'N/A';
        }

        return implode(', ', $this->risk_factors);
    }

    // ==================== SCOPES ====================

    /**
     * Scope para casos activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para casos inactivos
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope para casos resueltos
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope por número de intento
     */
    public function scopeByAttemptNumber($query, int $number)
    {
        return $query->where('attempt_number', $number);
    }

    /**
     * Scope para primeros intentos
     */
    public function scopeFirstAttempts($query)
    {
        return $query->where('attempt_number', 1);
    }

    /**
     * Scope para reintentos (intento > 1)
     */
    public function scopeRepeatedAttempts($query)
    {
        return $query->where('attempt_number', '>', 1);
    }

    /**
     * Scope con relaciones cargadas
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['patient', 'createdBy', 'updatedBy']);
    }

    /**
     * Scope por mecanismo utilizado
     */
    public function scopeByMechanism($query, string $mechanism)
    {
        return $query->where('mechanism', 'like', "%{$mechanism}%");
    }

    /**
     * Scope por vía de ingreso
     */
    public function scopeByAdmissionVia($query, string $via)
    {
        return $query->where('admission_via', $via);
    }

    /**
     * Scope por semana epidemiológica
     */
    public function scopeByWeek($query, int $week)
    {
        return $query->where('week_number', $week);
    }
}