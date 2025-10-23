<?php

// ================================
// ARCHIVO: app/Filament/Resources/PatientResource/RelationManagers/SubstanceConsumptionsRelationManager.php
// ================================

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubstanceConsumptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'substanceConsumptions';
    
    protected static ?string $title = 'Consumo de SPA';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('admission_date')
                    ->label('Fecha de Ingreso')
                    ->required(),
                Forms\Components\TextInput::make('diagnosis')
                    ->label('Diagnóstico')
                    ->required()
                    ->maxLength(500),
                Forms\Components\Select::make('consumption_level')
                    ->label('Nivel de Consumo')
                    ->options([
                        'Alto Riesgo' => 'Alto Riesgo',
                        'Riesgo Moderado' => 'Riesgo Moderado',
                        'Bajo Riesgo' => 'Bajo Riesgo',
                        'Perjudicial' => 'Perjudicial',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('admission_date')
                    ->label('Fecha Ingreso')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(40),
                Tables\Columns\TextColumn::make('consumption_level')
                    ->label('Nivel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alto Riesgo' => 'danger',
                        'Riesgo Moderado' => 'warning',
                        'Bajo Riesgo' => 'success',
                        'Perjudicial' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}