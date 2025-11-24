<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MentalDisorder extends Model
{
    use HasFactory, SoftDeletes;

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
        'created_by_id',
        'updated_by_id',
        // NOTA: 'assigned_to' NO existe en esta tabla, solo en patients
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'diagnosis_date' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    /**
     * Paciente asociado al trastorno
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
     * Verificar si fue dado de alta
     */
    public function isDischarged(): bool
    {
        return $this->status === 'discharged';
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
     * Scope para casos dados de alta
     */
    public function scopeDischarged($query)
    {
        return $query->where('status', 'discharged');
    }

    /**
     * Scope por tipo de ingreso
     */
    public function scopeByAdmissionType($query, string $type)
    {
        return $query->where('admission_type', $type);
    }

    /**
     * Scope con relaciones cargadas
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['patient', 'createdBy', 'updatedBy']);
    }

    /**
     * Scope por código de diagnóstico
     */
    public function scopeByDiagnosisCode($query, string $code)
    {
        return $query->where('diagnosis_code', 'like', "%{$code}%");
    }
}