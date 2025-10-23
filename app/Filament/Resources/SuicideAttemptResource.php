<?php
// ================================
// ARCHIVO: app/Filament/Resources/SuicideAttemptResource.php
// ================================

namespace App\Filament\Resources;

use App\Filament\Resources\SuicideAttemptResource\Pages;
use App\Models\SuicideAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SuicideAttemptResource extends Resource
{
    protected static ?string $model = SuicideAttempt::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Intentos de Suicidio';
    protected static ?string $modelLabel = 'Intento de Suicidio';
    protected static ?string $pluralModelLabel = 'Intentos de Suicidio';
    protected static ?string $navigationGroup = 'Gestión de Salud Mental';
    protected static ?int $navigationSort = 3;

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
                    
                Forms\Components\Section::make('Datos del Evento')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('event_date')
                                    ->label('Fecha del Evento')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                                    
                                Forms\Components\TextInput::make('week_number')
                                    ->label('Semana Epidemiológica')
                                    ->numeric()
                                    ->maxLength(2),
                                    
                                Forms\Components\TextInput::make('attempt_number')
                                    ->label('Número de Intento')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),
                            ]),
                            
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
                            ->default('URGENCIAS')
                            ->native(false),
                            
                        Forms\Components\TextInput::make('benefit_plan')
                            ->label('Plan de Beneficios')
                            ->maxLength(200),
                    ]),
                    
                Forms\Components\Section::make('Detalles del Intento')
                    ->schema([
                        Forms\Components\Textarea::make('trigger_factor')
                            ->label('Factor Desencadenante')
                            ->rows(3)
                            ->maxLength(500),
                            
                        Forms\Components\TagsInput::make('risk_factors')
                            ->label('Factores de Riesgo')
                            ->placeholder('Agregar factor de riesgo')
                            ->helperText('Presiona Enter después de cada factor'),
                            
                        Forms\Components\Textarea::make('mechanism')
                            ->label('Mecanismo Utilizado')
                            ->rows(3)
                            ->maxLength(1000),
                            
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
                                'resolved' => 'Resuelto',
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
                    
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Fecha Evento')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('N° Intento')
                    ->badge()
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('admission_via')
                    ->label('Ingreso Por')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('mechanism')
                    ->label('Mecanismo')
                    ->limit(30)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('status')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'resolved' => 'Resuelto',
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
            ->defaultSort('event_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuicideAttempts::route('/'),
            'create' => Pages\CreateSuicideAttempt::route('/create'),
            // 'view' => Pages\ViewSuicideAttempt::route('/{record}'),
            'edit' => Pages\EditSuicideAttempt::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}