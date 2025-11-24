<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubstanceConsumption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'admission_date',
        'admission_via',
        'diagnosis',
        'substances_used',
        'consumption_level',
        'additional_observation',
        'status',
        'created_by_id',
        'updated_by_id',
        // NOTA: 'assigned_to' NO existe en esta tabla, solo en patients
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'substances_used' => 'array', // JSON en la BD
    ];

    // ==================== RELACIONES ====================

    /**
     * Paciente asociado al consumo de sustancias
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
     * Verificar si está en tratamiento
     */
    public function isInTreatment(): bool
    {
        return $this->status === 'in_treatment';
    }

    /**
     * Verificar si está recuperado
     */
    public function isRecovered(): bool
    {
        return $this->status === 'recovered';
    }

    /**
     * Es alto riesgo
     */
    public function isHighRisk(): bool
    {
        return $this->consumption_level === 'Alto Riesgo';
    }

    /**
     * Es riesgo moderado
     */
    public function isModerateRisk(): bool
    {
        return $this->consumption_level === 'Riesgo Moderado';
    }

    /**
     * Es bajo riesgo
     */
    public function isLowRisk(): bool
    {
        return $this->consumption_level === 'Bajo Riesgo';
    }

    /**
     * Es perjudicial
     */
    public function isHarmful(): bool
    {
        return $this->consumption_level === 'Perjudicial';
    }

    /**
     * Obtener sustancias como string
     */
    public function getSubstancesStringAttribute(): string
    {
        if (!$this->substances_used || !is_array($this->substances_used)) {
            return 'N/A';
        }

        return implode(', ', $this->substances_used);
    }

    /**
     * Obtener número de sustancias
     */
    public function getSubstancesCountAttribute(): int
    {
        if (!$this->substances_used || !is_array($this->substances_used)) {
            return 0;
        }

        return count($this->substances_used);
    }

    /**
     * Verificar si usa múltiples sustancias
     */
    public function usesMultipleSubstances(): bool
    {
        return $this->substances_count > 1;
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
     * Scope para casos en tratamiento
     */
    public function scopeInTreatment($query)
    {
        return $query->where('status', 'in_treatment');
    }

    /**
     * Scope para casos recuperados
     */
    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    /**
     * Scope por nivel de consumo
     */
    public function scopeByConsumptionLevel($query, string $level)
    {
        return $query->where('consumption_level', $level);
    }

    /**
     * Scope para alto riesgo
     */
    public function scopeHighRisk($query)
    {
        return $query->where('consumption_level', 'Alto Riesgo');
    }

    /**
     * Scope para riesgo moderado
     */
    public function scopeModerateRisk($query)
    {
        return $query->where('consumption_level', 'Riesgo Moderado');
    }

    /**
     * Scope para bajo riesgo
     */
    public function scopeLowRisk($query)
    {
        return $query->where('consumption_level', 'Bajo Riesgo');
    }

    /**
     * Scope para perjudicial
     */
    public function scopeHarmful($query)
    {
        return $query->where('consumption_level', 'Perjudicial');
    }

    /**
     * Scope con relaciones cargadas
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['patient', 'createdBy', 'updatedBy']);
    }

    /**
     * Scope por vía de ingreso
     */
    public function scopeByAdmissionVia($query, string $via)
    {
        return $query->where('admission_via', $via);
    }

    /**
     * Scope para buscar por sustancia específica
     */
    public function scopeBySubstance($query, string $substance)
    {
        return $query->whereJsonContains('substances_used', $substance);
    }

    /**
     * Scope para consumo múltiple
     */
    public function scopeMultipleSubstances($query)
    {
        return $query->whereRaw('JSON_LENGTH(substances_used) > 1');
    }
}