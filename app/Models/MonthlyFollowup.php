<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MonthlyFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'followupable_id',
        'followupable_type',
        'followup_date',
        'year',
        'month',
        'description',
        'status',
        'next_followup',
        'actions_taken',
        'performed_by',
        'source_reference',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'next_followup' => 'date',
        'actions_taken' => 'array',
        'source_reference' => 'array',
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación polimórfica con el modelo seguido (MentalDisorder, SuicideAttempt, SubstanceConsumption)
     */
    public function followupable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que realizó el seguimiento
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Relación con User para Filament (alias de performedBy)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ==================== ATRIBUTOS Y ACCESSORS ====================

    /**
     * Obtener el paciente asociado al caso (a través de la relación polimórfica)
     */
    public function getPatientAttribute(): ?Patient
    {
        return $this->followupable?->patient;
    }

    /**
     * Obtener el tipo de caso en español
     */
    public function getCaseTypeAttribute(): string
    {
        return match ($this->followupable_type) {
            MentalDisorder::class => 'Trastorno Mental',
            SuicideAttempt::class => 'Intento Suicidio',
            SubstanceConsumption::class => 'Consumo SPA',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener información resumida del caso
     */
    public function getCaseInfoAttribute(): string
    {
        if (!$this->followupable) return 'N/A';

        return match ($this->followupable_type) {
            MentalDisorder::class =>
                ($this->followupable->diagnosis_code ?? 'Sin código') . ' - ' .
                Str::limit($this->followupable->diagnosis_description ?? 'Sin descripción', 50),
            SuicideAttempt::class =>
                'Intento #' . ($this->followupable->attempt_number ?? '1') . ' - ' .
                Str::limit($this->followupable->mechanism ?? 'Sin mecanismo', 50),
            SubstanceConsumption::class =>
                'Nivel: ' . ($this->followupable->consumption_level ?? 'N/A') . ' - ' .
                Str::limit($this->followupable->diagnosis ?? 'Sin diagnóstico', 50),
            default => 'N/A'
        };
    }

    /**
     * Obtener las acciones como string
     */
    public function getActionsAsStringAttribute(): string
    {
        if (!$this->actions_taken || !is_array($this->actions_taken)) {
            return '';
        }

        return implode(', ', $this->actions_taken);
    }

    /**
     * Obtener el nombre del mes
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }

    /**
     * Obtener color del badge según el tipo
     */
    public function getCaseTypeColorAttribute(): string
    {
        return match ($this->followupable_type) {
            MentalDisorder::class => 'primary',
            SuicideAttempt::class => 'danger',
            SubstanceConsumption::class => 'warning',
            default => 'gray'
        };
    }

    /**
     * Obtener icono según el tipo
     */
    public function getCaseTypeIconAttribute(): string
    {
        return match ($this->followupable_type) {
            MentalDisorder::class => 'heroicon-o-heart',
            SuicideAttempt::class => 'heroicon-o-exclamation-triangle',
            SubstanceConsumption::class => 'heroicon-o-beaker',
            default => 'heroicon-o-question-mark-circle'
        };
    }

    // ==================== MÉTODOS DE VERIFICACIÓN ====================

    /**
     * Verificar si es un seguimiento de trastorno mental
     */
    public function isMentalDisorderFollowup(): bool
    {
        return $this->followupable_type === MentalDisorder::class;
    }

    /**
     * Verificar si es un seguimiento de intento de suicidio
     */
    public function isSuicideAttemptFollowup(): bool
    {
        return $this->followupable_type === SuicideAttempt::class;
    }

    /**
     * Verificar si es un seguimiento de consumo SPA
     */
    public function isSubstanceConsumptionFollowup(): bool
    {
        return $this->followupable_type === SubstanceConsumption::class;
    }

    /**
     * Verificar si el seguimiento está completado
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verificar si el seguimiento está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si no se pudo contactar
     */
    public function wasNotContacted(): bool
    {
        return $this->status === 'not_contacted';
    }

    /**
     * Verificar si fue rechazado
     */
    public function wasRefused(): bool
    {
        return $this->status === 'refused';
    }

    /**
     * Verificar si tiene próxima cita programada
     */
    public function hasNextAppointment(): bool
    {
        return !is_null($this->next_followup);
    }

    /**
     * Verificar si la próxima cita está vencida
     */
    public function isNextAppointmentOverdue(): bool
    {
        if (!$this->next_followup) {
            return false;
        }

        return $this->next_followup < now();
    }

    /**
     * Verificar si es un seguimiento reciente
     */
    public function isRecent(int $days = 30): bool
    {
        return $this->followup_date >= now()->subDays($days);
    }

    // ==================== MÉTODOS DE INFORMACIÓN ====================

    /**
     * Obtener información del origen (para retrocompatibilidad)
     */
    public function getSourceInfo(): ?array
    {
        if (!$this->source_reference) {
            return null;
        }

        $source = $this->source_reference;

        if (isset($source['type']) && $source['type'] === 'mental_disorder' && isset($source['id'])) {
            $mentalDisorder = MentalDisorder::find($source['id']);
            if ($mentalDisorder) {
                return [
                    'type' => 'Trastorno Mental',
                    'description' => $mentalDisorder->diagnosis_description,
                    'code' => $mentalDisorder->diagnosis_code,
                    'model' => $mentalDisorder
                ];
            }
        }

        return null;
    }

    /**
     * Obtener detalles completos del caso
     */
    public function getCaseDetails(): array
    {
        if (!$this->followupable) {
            return ['type' => 'Desconocido', 'details' => []];
        }

        return match ($this->followupable_type) {
            MentalDisorder::class => [
                'type' => 'Trastorno Mental',
                'details' => [
                    'diagnosis_code' => $this->followupable->diagnosis_code,
                    'diagnosis_description' => $this->followupable->diagnosis_description,
                    'admission_type' => $this->followupable->admission_type,
                    'admission_date' => $this->followupable->admission_date,
                ]
            ],
            SuicideAttempt::class => [
                'type' => 'Intento Suicidio',
                'details' => [
                    'attempt_number' => $this->followupable->attempt_number,
                    'mechanism' => $this->followupable->mechanism,
                    'trigger_factor' => $this->followupable->trigger_factor,
                    'event_date' => $this->followupable->event_date,
                ]
            ],
            SubstanceConsumption::class => [
                'type' => 'Consumo SPA',
                'details' => [
                    'substances_used' => $this->followupable->substances_used,
                    'consumption_level' => $this->followupable->consumption_level,
                    'diagnosis' => $this->followupable->diagnosis,
                    'admission_date' => $this->followupable->admission_date,
                ]
            ],
            default => ['type' => 'Desconocido', 'details' => []]
        };
    }

    // ==================== SCOPES ====================

    /**
     * Scope para seguimientos por año y mes
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope para seguimientos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para seguimientos completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para seguimientos no contactados
     */
    public function scopeNotContacted($query)
    {
        return $query->where('status', 'not_contacted');
    }

    /**
     * Scope para seguimientos rechazados
     */
    public function scopeRefused($query)
    {
        return $query->where('status', 'refused');
    }

    /**
     * Scope para seguimientos por tipo de caso
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('followupable_type', $type);
    }

    /**
     * Scope para seguimientos de trastornos mentales
     */
    public function scopeMentalDisorders($query)
    {
        return $query->byType(MentalDisorder::class);
    }

    /**
     * Scope para seguimientos de intentos de suicidio
     */
    public function scopeSuicideAttempts($query)
    {
        return $query->byType(SuicideAttempt::class);
    }

    /**
     * Scope para seguimientos de consumo SPA
     */
    public function scopeSubstanceConsumptions($query)
    {
        return $query->byType(SubstanceConsumption::class);
    }

    /**
     * Scope para seguimientos por paciente (a través de cualquier tipo de caso)
     */
    public function scopeByPatient($query, int $patientId)
    {
        return $query->whereHasMorph(
            'followupable',
            [MentalDisorder::class, SuicideAttempt::class, SubstanceConsumption::class],
            function (Builder $q) use ($patientId) {
                $q->where('patient_id', $patientId);
            }
        );
    }

    /**
     * Scope para seguimientos recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('followup_date', '>=', now()->subDays($days));
    }

    /**
     * Scope con relaciones cargadas
     */
    public function scopeWithCases($query)
    {
        return $query->with([
            'followupable.patient',
            'user'
        ]);
    }

    /**
     * Scope para el año actual
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope para seguimientos por estado específico
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para seguimientos con citas vencidas
     */
    public function scopeOverdueAppointments($query)
    {
        return $query->where('next_followup', '<', now())
                    ->whereNotNull('next_followup');
    }

    /**
     * Scope para seguimientos del mes actual
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                    ->where('month', now()->month);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Estadísticas por tipo de caso para un período
     */
    public static function getStatsByType(int $year, ?int $month = null)
    {
        $query = static::where('year', $year);

        if ($month) {
            $query->where('month', $month);
        }

        return [
            'mental_disorders' => $query->clone()->mentalDisorders()->count(),
            'suicide_attempts' => $query->clone()->suicideAttempts()->count(),
            'substance_consumptions' => $query->clone()->substanceConsumptions()->count(),
            'total' => $query->count(),
            'by_status' => [
                'completed' => $query->clone()->where('status', 'completed')->count(),
                'pending' => $query->clone()->where('status', 'pending')->count(),
                'not_contacted' => $query->clone()->where('status', 'not_contacted')->count(),
                'refused' => $query->clone()->where('status', 'refused')->count(),
            ]
        ];
    }

    /**
     * Obtener seguimientos pendientes por tipo
     */
    public static function getPendingByType(): array
    {
        return [
            'mental_disorders' => static::mentalDisorders()->pending()->count(),
            'suicide_attempts' => static::suicideAttempts()->pending()->count(),
            'substance_consumptions' => static::substanceConsumptions()->pending()->count(),
        ];
    }

    /**
     * Obtener seguimientos del mes actual por tipo
     */
    public static function getCurrentMonthByType(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return [
            'mental_disorders' => static::mentalDisorders()->forPeriod($currentYear, $currentMonth)->count(),
            'suicide_attempts' => static::suicideAttempts()->forPeriod($currentYear, $currentMonth)->count(),
            'substance_consumptions' => static::substanceConsumptions()->forPeriod($currentYear, $currentMonth)->count(),
        ];
    }

    /**
     * Obtener resumen de estado de seguimientos
     */
    public static function getStatusSummary(): array
    {
        return [
            'total' => static::count(),
            'completed' => static::completed()->count(),
            'pending' => static::pending()->count(),
            'not_contacted' => static::notContacted()->count(),
            'refused' => static::refused()->count(),
            'recent' => static::recent()->count(),
            'overdue' => static::overdueAppointments()->count(),
        ];
    }
}