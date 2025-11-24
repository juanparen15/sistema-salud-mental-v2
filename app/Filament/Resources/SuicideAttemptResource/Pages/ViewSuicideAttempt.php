<?php

namespace App\Filament\Resources\SuicideAttemptResource\Pages;

use App\Filament\Resources\SuicideAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSuicideAttempt extends ViewRecord
{
    protected static string $resource = SuicideAttemptResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Intento de Suicidio - {$record->patient->full_name}";
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

                Infolists\Components\Section::make('Datos del Evento')
                    ->schema([
                        Infolists\Components\TextEntry::make('event_date')
                            ->label('Fecha del Evento')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('week_number')
                            ->label('Semana Epidemiológica')
                            ->placeholder('No especificada'),

                        Infolists\Components\TextEntry::make('attempt_number')
                            ->label('Número de Intento')
                            ->badge()
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('admission_via')
                            ->label('Ingreso Por')
                            ->badge(),

                        Infolists\Components\TextEntry::make('benefit_plan')
                            ->label('Plan de Beneficios')
                            ->placeholder('No especificado')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Detalles del Intento')
                    ->schema([
                        Infolists\Components\TextEntry::make('trigger_factor')
                            ->label('Factor Desencadenante')
                            ->placeholder('No especificado')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('risk_factors')
                            ->label('Factores de Riesgo')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state) && !empty($state)) {
                                    return implode(', ', $state);
                                }
                                return 'No especificados';
                            })
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('mechanism')
                            ->label('Mecanismo Utilizado')
                            ->placeholder('No especificado')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->placeholder('Sin observaciones')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Estado')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'danger',
                                'inactive' => 'warning',
                                'resolved' => 'success',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'resolved' => 'Resuelto',
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
