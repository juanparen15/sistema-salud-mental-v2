<?php

namespace App\Filament\Resources\MentalDisorderResource\Pages;

use App\Filament\Resources\MentalDisorderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMentalDisorder extends ViewRecord
{
    protected static string $resource = MentalDisorderResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Trastorno Mental - {$record->patient->full_name}";
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

                        Infolists\Components\TextEntry::make('admission_type')
                            ->label('Tipo de Ingreso')
                            ->badge(),

                        Infolists\Components\TextEntry::make('admission_via')
                            ->label('Ingreso Por')
                            ->badge(),

                        Infolists\Components\TextEntry::make('service_area')
                            ->label('Área de Servicio')
                            ->placeholder('No especificada'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Diagnóstico')
                    ->schema([
                        Infolists\Components\TextEntry::make('diagnosis_code')
                            ->label('Código Diagnóstico (CIE-10)')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('diagnosis_date')
                            ->label('Fecha de Diagnóstico')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('diagnosis_type')
                            ->label('Tipo de Diagnóstico')
                            ->badge(),

                        Infolists\Components\TextEntry::make('diagnosis_description')
                            ->label('Descripción del Diagnóstico')
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Observaciones y Estado')
                    ->schema([
                        Infolists\Components\TextEntry::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->placeholder('Sin observaciones')
                            ->html()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'discharged' => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'discharged' => 'Dado de Alta',
                            }),
                    ])
                    ->columns(2),

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
