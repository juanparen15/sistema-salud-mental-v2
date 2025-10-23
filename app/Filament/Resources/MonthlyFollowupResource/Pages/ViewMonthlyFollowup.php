<?php

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMonthlyFollowup extends ViewRecord
{
    protected static string $resource = MonthlyFollowupResource::class;

    public function mount(int|string $record): void
    {
        // abort_unless(auth()->user()->can('view_followups'), 403);
        parent::mount($record);
    }

    public function getTitle(): string
    {
        $followup = $this->getRecord();
        if ($followup->followupable && $followup->followupable->patient) {
            return "Seguimiento de {$followup->followupable->patient->full_name}";
        }
        return 'Ver Seguimiento Mensual';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Paciente')
                    ->schema([
                        Infolists\Components\TextEntry::make('patient_info')
                            ->label('Paciente')
                            ->formatStateUsing(function ($record) {
                                if ($record->followupable && $record->followupable->patient) {
                                    $patient = $record->followupable->patient;
                                    return "{$patient->full_name} - {$patient->document_number}";
                                }
                                return 'N/A';
                            }),

                        Infolists\Components\TextEntry::make('case_type')
                            ->label('Tipo de Caso')
                            ->formatStateUsing(function ($record) {
                                return match ($record->followupable_type) {
                                    'App\Models\MentalDisorder' => '❤️ Trastorno Mental',
                                    'App\Models\SuicideAttempt' => '⚠️ Intento de Suicidio',
                                    'App\Models\SubstanceConsumption' => '🧪 Consumo SPA',
                                    default => 'Desconocido'
                                };
                            }),

                        Infolists\Components\TextEntry::make('case_details')
                            ->label('Detalles del Caso')
                            ->formatStateUsing(function ($record) {
                                if (!$record->followupable) return 'N/A';

                                return match ($record->followupable_type) {
                                    'App\Models\MentalDisorder' =>
                                    "Código CIE-10: " . ($record->followupable->diagnosis_code ?? 'Sin código') . "\n" .
                                        "Diagnóstico: " . ($record->followupable->diagnosis_description ?? 'Sin descripción'),
                                    'App\Models\SuicideAttempt' =>
                                    "Intento N°: " . ($record->followupable->attempt_number ?? '1') . "\n" .
                                        "Factor desencadenante: " . ($record->followupable->trigger_factor ?? 'N/A'),
                                    'App\Models\SubstanceConsumption' =>
                                    "Nivel de consumo: " . ($record->followupable->consumption_level ?? 'N/A') . "\n" .
                                        "Diagnóstico: " . ($record->followupable->diagnosis ?? 'Sin diagnóstico'),
                                    default => 'N/A'
                                };
                            })
                            ->html(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Información del Seguimiento')
                    ->schema([
                        Infolists\Components\TextEntry::make('followup_date')
                            ->label('Fecha del Seguimiento')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'completed' => '✅ Completado',
                                'pending' => '⏳ Pendiente',
                                'not_contacted' => '❌ No Contactado',
                                'refused' => '🚫 Rechazado',
                                default => ucfirst($state),
                            })
                            ->color(fn(string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'not_contacted' => 'danger',
                                'refused' => 'secondary',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('year')
                            ->label('Año'),

                        Infolists\Components\TextEntry::make('month')
                            ->label('Mes')
                            ->formatStateUsing(fn($state) => match ((int)$state) {
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
                                12 => 'Diciembre',
                                default => $state
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Detalles del Seguimiento')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('actions_taken')
                            ->label('Acciones Realizadas')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return "• " . implode("\n• ", $state);
                                }
                                return $state ?: 'Sin acciones registradas';
                            })
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('next_followup')
                            ->label('Próximo Seguimiento')
                            ->date('d/m/Y')
                            ->placeholder('No programado'),
                    ]),

                Infolists\Components\Section::make('Información de Registro')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Registrado por'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
        ];
    }
}
