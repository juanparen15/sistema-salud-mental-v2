<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Builder;

// class Patient extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'document_number',
//         'document_type',
//         'full_name',
//         'gender',
//         'birth_date',
//         'phone',
//         'address',
//         'neighborhood',
//         'village',
//         'eps_code',
//         'eps_name',
//         'status',
//     ];

//     protected $casts = [
//         'birth_date' => 'date',
//     ];

//     // protected $appends = ['age'];

//     // public function getAgeAttribute(): int
//     // {
//     //     return $this->birth_date->age;
//     // }

//     protected $appends = ['age'];

//     public function getAgeAttribute(): int
//     {
//         return $this->birth_date->age;
//     }

//     public function mentalDisorders(): HasMany
//     {
//         return $this->hasMany(MentalDisorder::class);
//     }

//     public function suicideAttempts(): HasMany
//     {
//         return $this->hasMany(SuicideAttempt::class);
//     }

//     public function substanceConsumptions(): HasMany
//     {
//         return $this->hasMany(SubstanceConsumption::class);
//     }

//     public function scopeSearch(Builder $query, ?string $search): Builder
//     {
//         return $query->when(
//             $search,
//             fn($q) => $q
//                 ->where('document_number', 'like', "%{$search}%")
//                 ->orWhere('full_name', 'like', "%{$search}%")
//         );
//     }

//     public function getActiveConditionsAttribute(): array
//     {
//         $conditions = [];

//         if ($this->mentalDisorders()->where('status', 'active')->exists()) {
//             $conditions[] = 'Trastorno Mental';
//         }

//         if ($this->suicideAttempts()->where('status', 'active')->exists()) {
//             $conditions[] = 'Intento Suicidio';
//         }

//         if ($this->substanceConsumptions()->whereIn('status', ['active', 'in_treatment'])->exists()) {
//             $conditions[] = 'Consumo SPA';
//         }

//         return $conditions;
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'document_type',
        'full_name',
        'gender',
        'birth_date',
        'phone',
        'address',
        'neighborhood',
        'village',
        'eps_code',
        'eps_name',
        'status',
        'created_by_id',
        'assigned_to',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relación polimórfica con seguimientos mensuales
     */
    public function monthlyFollowups(): MorphMany
    {
        return $this->morphMany(MonthlyFollowup::class, 'followupable');
    }

    /**
     * Relación directa con seguimientos mensuales (para Filament)
     */
    public function followups(): HasMany
    {
        return $this->hasMany(MonthlyFollowup::class, 'followupable_id')
            ->where('followupable_type', self::class);
    }

    /**
     * Obtener edad calculada desde birth_date
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    /**
     * Obtener último seguimiento
     */
    public function getLatestFollowupAttribute()
    {
        return $this->monthlyFollowups()->latest('followup_date')->first();
    }

    /**
     * Verificar si tiene seguimientos recientes
     */
    public function hasRecentFollowup(int $days = 30): bool
    {
        return $this->monthlyFollowups()
            ->where('followup_date', '>=', now()->subDays($days))
            ->exists();
    }

    /**
     * Obtener seguimientos por año
     */
    public function getFollowupsByYear(int $year)
    {
        return $this->monthlyFollowups()
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
     * Scope para pacientes activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para búsqueda de pacientes
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('full_name', 'like', "%{$search}%")
            ->orWhere('document_number', 'like', "%{$search}%");
    }

    /**
     * Scope para pacientes con seguimientos recientes
     */
    public function scopeWithRecentFollowups($query, int $days = 30)
    {
        return $query->whereHas('monthlyFollowups', function ($q) use ($days) {
            $q->where('followup_date', '>=', now()->subDays($days));
        });
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
