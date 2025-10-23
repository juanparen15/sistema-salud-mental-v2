<?php
// ================================
// ARCHIVO: app/Filament/Widgets/StatsOverviewWidget.php
// WIDGET DE ESTADÍSTICAS PRINCIPALES DEL DASHBOARD
// ================================

namespace App\Filament\Widgets;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Models\MonthlyFollowup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = '30s'; // Actualización cada 30 segundos

    protected function getStats(): array
    {
        return [
            // Stat 1: Pacientes Activos
            Stat::make('Pacientes Activos', $this->getActivePatientsCount())
                ->description($this->getActivePatientsDescription())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getPatientsTrendChart())
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            // Stat 2: Trastornos Mentales
            Stat::make('Trastornos Mentales', $this->getActiveMentalDisordersCount())
                ->description($this->getMentalDisordersDescription())
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning')
                ->chart($this->getMentalDisordersTrendChart()),

            // Stat 3: Intentos de Suicidio
            Stat::make('Intentos de Suicidio', $this->getActiveSuicideAttemptsCount())
                ->description($this->getSuicideAttemptsDescription())
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('danger')
                ->chart($this->getSuicideAttemptsTrendChart()),

            // Stat 4: Consumo de SPA
            Stat::make('Consumo SPA', $this->getActiveSubstanceConsumptionsCount())
                ->description($this->getSubstanceConsumptionsDescription())
                ->descriptionIcon('heroicon-m-beaker')
                ->color('info')
                ->chart($this->getSubstanceConsumptionsTrendChart()),

            // Stat 5: Seguimientos Pendientes
            Stat::make('Seguimientos Pendientes', $this->getPendingFollowupsCount())
                ->description($this->getPendingFollowupsDescription())
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->chart($this->getFollowupsTrendChart()),

            // Stat 6: Seguimientos Completados (este mes)
            Stat::make('Seguimientos Completados', $this->getCompletedFollowupsThisMonth())
                ->description('Realizados este mes (' . now()->format('F') . ')')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getCompletedFollowupsChart()),
        ];
    }

    // ==========================================
    // MÉTODOS PARA PACIENTES
    // ==========================================

    protected function getActivePatientsCount(): int
    {
        return Patient::where('status', 'active')->count();
    }

    protected function getActivePatientsDescription(): string
    {
        $totalPatients = Patient::count();
        $percentage = $totalPatients > 0 
            ? round(($this->getActivePatientsCount() / $totalPatients) * 100, 1) 
            : 0;
        
        return "{$percentage}% del total de pacientes";
    }

    protected function getPatientsTrendChart(): array
    {
        // Últimos 7 días
        return Patient::where('status', 'active')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    // ==========================================
    // MÉTODOS PARA TRASTORNOS MENTALES
    // ==========================================

    protected function getActiveMentalDisordersCount(): int
    {
        return MentalDisorder::where('status', 'active')->count();
    }

    protected function getMentalDisordersDescription(): string
    {
        $thisMonth = MentalDisorder::whereMonth('admission_date', now()->month)
            ->whereYear('admission_date', now()->year)
            ->count();
        
        return "Casos activos (+{$thisMonth} este mes)";
    }

    protected function getMentalDisordersTrendChart(): array
    {
        return MentalDisorder::where('admission_date', '>=', now()->subDays(7))
            ->selectRaw('DATE(admission_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    // ==========================================
    // MÉTODOS PARA INTENTOS DE SUICIDIO
    // ==========================================

    protected function getActiveSuicideAttemptsCount(): int
    {
        return SuicideAttempt::where('status', 'active')->count();
    }

    protected function getSuicideAttemptsDescription(): string
    {
        $thisMonth = SuicideAttempt::whereMonth('event_date', now()->month)
            ->whereYear('event_date', now()->year)
            ->count();
        
        return "En seguimiento (+{$thisMonth} este mes)";
    }

    protected function getSuicideAttemptsTrendChart(): array
    {
        return SuicideAttempt::where('event_date', '>=', now()->subDays(7))
            ->selectRaw('DATE(event_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    // ==========================================
    // MÉTODOS PARA CONSUMO DE SPA
    // ==========================================

    protected function getActiveSubstanceConsumptionsCount(): int
    {
        return SubstanceConsumption::whereIn('status', ['active', 'in_treatment'])->count();
    }

    protected function getSubstanceConsumptionsDescription(): string
    {
        $inTreatment = SubstanceConsumption::where('status', 'in_treatment')->count();
        
        return "En tratamiento: {$inTreatment}";
    }

    protected function getSubstanceConsumptionsTrendChart(): array
    {
        return SubstanceConsumption::where('admission_date', '>=', now()->subDays(7))
            ->selectRaw('DATE(admission_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    // ==========================================
    // MÉTODOS PARA SEGUIMIENTOS
    // ==========================================

    protected function getPendingFollowupsCount(): int
    {
        return MonthlyFollowup::where('status', 'pending')
            ->whereMonth('followup_date', now()->month)
            ->whereYear('followup_date', now()->year)
            ->count();
    }

    protected function getPendingFollowupsDescription(): string
    {
        $overdue = MonthlyFollowup::where('status', 'pending')
            ->where('followup_date', '<', now())
            ->count();
        
        return $overdue > 0 
            ? "⚠️ {$overdue} vencidos" 
            : "Por realizar este mes";
    }

    protected function getFollowupsTrendChart(): array
    {
        // Seguimientos de los últimos 7 días
        return MonthlyFollowup::where('followup_date', '>=', now()->subDays(7))
            ->selectRaw('DATE(followup_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getCompletedFollowupsThisMonth(): int
    {
        return MonthlyFollowup::where('status', 'completed')
            ->whereMonth('followup_date', now()->month)
            ->whereYear('followup_date', now()->year)
            ->count();
    }

    protected function getCompletedFollowupsChart(): array
    {
        // Seguimientos completados por semana del mes actual
        $weeks = [];
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        for ($week = 1; $week <= 4; $week++) {
            $startDate = $startOfMonth->copy()->addWeeks($week - 1);
            $endDate = $startDate->copy()->addWeek();
            
            if ($endDate->greaterThan($endOfMonth)) {
                $endDate = $endOfMonth;
            }
            
            $count = MonthlyFollowup::where('status', 'completed')
                ->whereBetween('followup_date', [$startDate, $endDate])
                ->count();
            
            $weeks[] = $count;
        }
        
        return $weeks;
    }

    // ==========================================
    // MÉTODOS ADICIONALES
    // ==========================================

    /**
     * Determinar si el widget puede ser visto por el usuario actual
     */
    public static function canView(): bool
    {
        return true; // Puedes agregar lógica de permisos aquí
    }
}