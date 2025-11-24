<?php

namespace App\Filament\Resources\SubstanceConsumptionResource\Pages;

use App\Filament\Resources\SubstanceConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSubstanceConsumption extends ViewRecord
{
    protected static string $resource = SubstanceConsumptionResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Consumo SPA - {$record->patient->full_name}";
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Paciente')
                    ->schema([
                        Infolists\Components\TextEntry::make('patient.full_name')
                            ->label('Paciente')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('patient.document_number')
                            ->label('Documento')
                            ->copyable(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Datos de Ingreso')
                    ->schema([
                        Infolists\Components\TextEntry::make('admission_date')
                            ->label('Fecha de Ingreso')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('admission_via')
                            ->label('Ingreso Por')
                            ->badge(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Información del Consumo')
                    ->schema([
                        Infolists\Components\TextEntry::make('diagnosis')
                            ->label('Diagnóstico')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('substances_used')
                            ->label('Sustancias Utilizadas')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state) && !empty($state)) {
                                    return implode(', ', $state);
                                }
                                return 'No especificadas';
                            })
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('consumption_level')
                            ->label('Nivel de Consumo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Alto Riesgo' => 'danger',
                                'Riesgo Moderado' => 'warning',
                                'Bajo Riesgo' => 'success',
                                'Perjudicial' => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->placeholder('Sin observaciones')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Estado')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'warning',
                                'inactive' => 'gray',
                                'in_treatment' => 'info',
                                'recovered' => 'success',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'in_treatment' => 'En Tratamiento',
                                'recovered' => 'Recuperado',
                            }),
                    ]),

                Infolists\Components\Section::make('Seguimientos Mensuales')
                    ->schema([
                        Infolists\Components\ViewEntry::make('followups_list')
                            ->label('')
                            ->view('filament.infolists.followups-list')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Información de Registro')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Registrado')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
