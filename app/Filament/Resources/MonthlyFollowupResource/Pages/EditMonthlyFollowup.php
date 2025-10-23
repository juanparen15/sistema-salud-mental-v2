<?php

// ================================
// EDIT MONTHLY FOLLOWUP - ACTUALIZADA
// ================================

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditMonthlyFollowup extends EditRecord
{
    protected static string $resource = MonthlyFollowupResource::class;

    // ✅ Control de acceso
    public function mount(int|string $record): void
    {
        // $followup = $this->getRecord();

        // Verificar si puede editar este seguimiento específico
        // if (!auth()->user()->can('edit_all_followups')) {
        //     if (!auth()->user()->can('edit_followups') || $this->getRecord()->performed_by !== auth()->id()) {
        //         abort(403, 'No tienes permisos para editar este seguimiento.');
        //     }
        // }

        parent::mount($record);
    }

    public function getTitle(): string
    {
        $followup = $this->getRecord();
        if ($followup->followupable && $followup->followupable->patient) {
            return "Editando Seguimiento de {$followup->followupable->patient->full_name}";
        }
        return 'Editar Seguimiento Mensual';
    }

    public function getSubheading(): ?string
    {
        $followup = $this->getRecord();
        $date = $followup->followup_date?->format('d/m/Y') ?? 'Sin fecha';
        $status = match ($followup->status) {
            'pending' => '⏳ Pendiente',
            'completed' => '✅ Completado',
            'not_contacted' => '❌ No Contactado',
            'refused' => '🚫 Rechazado',
            default => $followup->status,
        };

        return "Seguimiento del {$date} | Estado: {$status}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            // ✅ Acción para duplicar seguimiento
            Actions\Action::make('duplicate')
                ->label('Crear Seguimiento Similar')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->action(function () {
                    $currentRecord = $this->getRecord();

                    return redirect()->to(
                        static::getResource()::getUrl('create', [
                            'source_type' => match ($currentRecord->followupable_type) {
                                'App\Models\MentalDisorder' => 'mental_disorder',
                                'App\Models\SuicideAttempt' => 'suicide_attempt',
                                'App\Models\SubstanceConsumption' => 'substance_consumption',
                                default => null,
                            },
                            'source_id' => $currentRecord->followupable_id,
                        ])
                    );
                }),

            // ✅ Acción para marcar como completado
            Actions\Action::make('mark_completed')
                ->label('Marcar Completado')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->getRecord()->update(['status' => 'completed']);

                    Notification::make()
                        ->title('Seguimiento marcado como completado')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                })
                ->visible(fn() =>
                $this->getRecord()->status !== 'completed' &&
                    $this->getRecord()->performed_by === auth()->id()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ✅ Mantener el usuario original que creó el seguimiento
        $data['performed_by'] = $this->getRecord()->performed_by;

        // Actualizar año y mes si cambió la fecha
        if (isset($data['followup_date'])) {
            $date = \Carbon\Carbon::parse($data['followup_date']);
            $data['year'] = $date->year;
            $data['month'] = $date->month;
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Seguimiento actualizado correctamente';
    }
}
