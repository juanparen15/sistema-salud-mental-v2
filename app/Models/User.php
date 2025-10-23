<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'professional_id',
        'department',
        'position',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Solo usuarios activos pueden acceder
        if (!$this->is_active) {
            return false;
        }

        // Super admin siempre tiene acceso
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Verificar si tiene rol panel_user o cualquier otro rol
        return $this->hasAnyRole([
            'panel_user',
            'administrador',
            'coordinador_salud_mental',
            'profesional_salud',
            'psicologo',
            'psiquiatra',
            'trabajador_social',
            'enfermero',
            'auxiliar',
            'auditor',
            'visor'
        ]);
    }

    // Métodos helper para verificar roles específicos
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isCoordinator(): bool
    {
        return $this->hasRole('coordinador_salud_mental');
    }

    public function isProfessional(): bool
    {
        return $this->hasAnyRole([
            'profesional_salud',
            'psicologo',
            'psiquiatra',
            'trabajador_social',
            'enfermero'
        ]);
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('auditor');
    }

    public function canManageHighRisk(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'coordinador_salud_mental',
            'psiquiatra',
            'psicologo'
        ]);
    }

    public function canExportData(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'administrador',
            'coordinador_salud_mental',
            'auditor'
        ]);
    }
}
