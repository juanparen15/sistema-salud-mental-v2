<?php

// ================================
// ARCHIVO: app/Filament/Resources/SubstanceConsumptionResource.php
// ================================

namespace App\Filament\Resources;

use App\Filament\Resources\SubstanceConsumptionResource\Pages;
use App\Models\SubstanceConsumption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SubstanceConsumptionResource extends Resource
{
    protected static ?string $model = SubstanceConsumption::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Consumo SPA';
    protected static ?string $modelLabel = 'Consumo de SPA';
    protected static ?string $pluralModelLabel = 'Consumo de SPA';
    protected static ?string $navigationGroup = 'Gestión de Salud Mental';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Paciente')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('Datos de Ingreso')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('admission_date')
                                    ->label('Fecha de Ingreso')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                                    
                                Forms\Components\Select::make('admission_via')
                                    ->label('Ingreso Por')
                                    ->options([
                                        'URGENCIAS' => 'Urgencias',
                                        'CONSULTA_EXTERNA' => 'Consulta Externa',
                                        'HOSPITALIZACION' => 'Hospitalización',
                                        'REFERENCIA' => 'Referencia',
                                        'COMUNIDAD' => 'Comunidad',
                                    ])
                                    ->required()
                                    ->default('CONSULTA_EXTERNA')
                                    ->native(false),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Información del Consumo')
                    ->schema([
                        Forms\Components\TextInput::make('diagnosis')
                            ->label('Diagnóstico')
                            ->required()
                            ->maxLength(500),
                            
                        Forms\Components\TagsInput::make('substances_used')
                            ->label('Sustancias Utilizadas')
                            ->placeholder('Agregar sustancia')
                            ->helperText('Presiona Enter después de cada sustancia')
                            ->suggestions([
                                'Alcohol',
                                'Marihuana',
                                'Cocaína',
                                'Basuco',
                                'Heroína',
                                'Éxtasis',
                                'LSD',
                                'Inhalantes',
                                'Benzodiacepinas',
                                'Anfetaminas',
                            ]),
                            
                        Forms\Components\Select::make('consumption_level')
                            ->label('Nivel de Consumo')
                            ->options([
                                'Alto Riesgo' => 'Alto Riesgo',
                                'Riesgo Moderado' => 'Riesgo Moderado',
                                'Bajo Riesgo' => 'Bajo Riesgo',
                                'Perjudicial' => 'Perjudicial',
                            ])
                            ->required()
                            ->default('Bajo Riesgo')
                            ->native(false),
                            
                        Forms\Components\Textarea::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
                    
                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'in_treatment' => 'En Tratamiento',
                                'recovered' => 'Recuperado',
                            ])
                            ->required()
                            ->default('active')
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('admission_date')
                    ->label('Fecha Ingreso')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(40)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('consumption_level')
                    ->label('Nivel de Riesgo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alto Riesgo' => 'danger',
                        'Riesgo Moderado' => 'warning',
                        'Bajo Riesgo' => 'success',
                        'Perjudicial' => 'danger',
                    }),
                    
                Tables\Columns\TextColumn::make('status')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('consumption_level')
                    ->label('Nivel de Consumo')
                    ->options([
                        'Alto Riesgo' => 'Alto Riesgo',
                        'Riesgo Moderado' => 'Riesgo Moderado',
                        'Bajo Riesgo' => 'Bajo Riesgo',
                        'Perjudicial' => 'Perjudicial',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'in_treatment' => 'En Tratamiento',
                        'recovered' => 'Recuperado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('admission_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubstanceConsumptions::route('/'),
            'create' => Pages\CreateSubstanceConsumption::route('/create'),
            // 'view' => Pages\ViewSubstanceConsumption::route('/{record}'),
            'edit' => Pages\EditSubstanceConsumption::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['active', 'in_treatment'])->count();
    }
}