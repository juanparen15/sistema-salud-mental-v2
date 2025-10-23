<?php

// ================================
// CREATE MONTHLY FOLLOWUP - ACTUALIZADA
// ================================

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use App\Models\MonthlyFollowup;
use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMonthlyFollowup extends CreateRecord
{
    protected static string $resource = MonthlyFollowupResource::class;

    // ✅ Control de acceso
    public function mount(): void
    {
        // abort_unless(auth()->user()->can('create_followups'), 403);
        parent::mount();
    }

    public function getTitle(): string
    {
        $sourceType = request()->query('source_type');
        $patientId = request()->query('patient_id');

        if ($patientId) {
            $patient = Patient::find($patientId);
            if ($patient) {
                return "Nuevo Seguimiento para {$patient->full_name}";
            }
        }

        return 'Nuevo Seguimiento Mensual';
    }

    public function getSubheading(): ?string
    {
        $sourceType = request()->query('source_type');
        $sourceId = request()->query('source_id');

        if ($sourceType && $sourceId) {
            return match ($sourceType) {
                'mental_disorder' => optional(MentalDisorder::with('patient')->find($sourceId), function ($disorder) {
                    return "Trastorno Mental: {$disorder->diagnosis_description} | Documento: {$disorder->patient->document_number}";
                }),
                'suicide_attempt' => optional(SuicideAttempt::with('patient')->find($sourceId), function ($attempt) {
                    return "Intento de Suicidio N° {$attempt->attempt_number} | Documento: {$attempt->patient->document_number}";
                }),
                'substance_consumption' => optional(SubstanceConsumption::with('patient')->find($sourceId), function ($consumption) {
                    return "Consumo SPA: {$consumption->diagnosis} | Documento: {$consumption->patient->document_number}";
                }),
                default => null,
            };
        }

        return 'Complete la información del seguimiento mensual';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sourceType = request()->query('source_type');
        $sourceId = request()->query('source_id');

        // ✅ Asignar usuario que realiza el seguimiento
        $data['performed_by'] = auth()->id();

        // Configurar el tipo de caso según los parámetros de URL
        if ($sourceType && $sourceId) {
            switch ($sourceType) {
                case 'mental_disorder':
                    $data['followupable_type'] = MentalDisorder::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                case 'suicide_attempt':
                    $data['followupable_type'] = SuicideAttempt::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                case 'substance_consumption':
                    $data['followupable_type'] = SubstanceConsumption::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                default:
                    $data['followupable_type'] = Patient::class;
                    $data['followupable_id'] = request()->query('patient_id');
            }
        } else {
            // Fallback al paciente directo si no hay source
            $data['followupable_type'] = Patient::class;
            $data['followupable_id'] = $data['patient_id'] ?? request()->query('patient_id');
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // ✅ Verificar si ya existe un seguimiento para este caso en este mes
        $existingFollowup = MonthlyFollowup::where('followupable_id', $data['followupable_id'])
            ->where('followupable_type', $data['followupable_type'])
            ->where('year', $data['year'])
            ->where('month', $data['month'])
            ->first();

        if ($existingFollowup) {
            // Mostrar notificación de que ya existe
            Notification::make()
                ->title('Seguimiento ya existe')
                ->body("Ya existe un seguimiento para este caso en {$this->getMonthName($data['month'])} {$data['year']}.")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ver_existente')
                        ->button()
                        ->url($this->getResource()::getUrl('edit', ['record' => $existingFollowup]))
                        ->label('Editar seguimiento existente'),
                ])
                ->persistent()
                ->send();

            return $existingFollowup;
        }

        // Si no existe, crear normalmente
        try {
            return static::getModel()::create($data);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al crear seguimiento')
                ->body('No se pudo crear el seguimiento: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $e;
        }
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        return $months[$month] ?? (string) $month;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Seguimiento mensual registrado correctamente';
    }
}
