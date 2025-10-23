<?php

// ================================
// LIST MONTHLY FOLLOWUPS - ACTUALIZADA
// ================================

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListMonthlyFollowups extends ListRecords
{
    protected static string $resource = MonthlyFollowupResource::class;

    public function getTitle(): string
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'usuario';

        return match ($role) {
            'coordinator', 'admin', 'super_admin' => 'Gestión de Seguimientos Mensuales',
            'psychologist' => 'Mis Seguimientos Psicológicos',
            'social_worker' => 'Mis Seguimientos Sociales',
            'assistant' => 'Seguimientos Registrados',
            default => 'Seguimientos Mensuales',
        };
    }

    public function getSubheading(): ?string
    {
        // ✅ Mostrar estadísticas contextuales según permisos
        $query = $this->getResource()::getEloquentQuery();

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'completed_this_month' => (clone $query)->where('status', 'completed')
                ->whereMonth('followup_date', now()->month)
                ->whereYear('followup_date', now()->year)
                ->count(),
        ];

        return "Total: {$stats['total']} | Pendientes: {$stats['pending']} | Completados este mes: {$stats['completed_this_month']}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // ✅ Acción para generar reporte de seguimientos
            // Actions\Action::make('generate_report')
            //     ->label('Generar Reporte')
            //     ->icon('heroicon-o-document-chart-bar')
            //     ->color('info')
            //     ->url(fn() => route('filament.admin.pages.reportes')),

            // ✅ Acción para ver seguimientos vencidos
            Actions\Action::make('view_overdue')
                ->label('Ver Vencidos')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->action(function () {
                    $this->tableFilters['overdue_followups']['isActive'] = true;
                    $this->updatedTableFilters();

                    Notification::make()
                        ->title('Mostrando seguimientos vencidos')
                        ->info()
                        ->send();
                })
                ->badge(function () {
                    return $this->getResource()::getEloquentQuery()
                        ->where('next_followup', '<', now())
                        ->whereNotNull('next_followup')
                        ->count();
                })
                ->badgeColor('danger'),
        ];
    }
}
