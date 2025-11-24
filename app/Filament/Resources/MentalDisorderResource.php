<?php
// ================================
// ARCHIVO: app/Filament/Resources/MentalDisorderResource.php
// ================================

namespace App\Filament\Resources;

use App\Filament\Resources\MentalDisorderResource\Pages;
use App\Models\MentalDisorder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MentalDisorderResource extends Resource
{
    protected static ?string $model = MentalDisorder::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Trastornos Mentales';
    protected static ?string $modelLabel = 'Trastorno Mental';
    protected static ?string $pluralModelLabel = 'Trastornos Mentales';
    protected static ?string $navigationGroup = 'Gestión de Salud Mental';
    protected static ?int $navigationSort = 2;

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
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('full_name')
                                    ->label('Nombre Completo')
                                    ->required(),
                                Forms\Components\TextInput::make('document_number')
                                    ->label('Número de Documento')
                                    ->required(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Datos de Ingreso')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('admission_date')
                                    ->label('Fecha de Ingreso')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                                    
                                Forms\Components\Select::make('admission_type')
                                    ->label('Tipo de Ingreso')
                                    ->options([
                                        'AMBULATORIO' => 'Ambulatorio',
                                        'HOSPITALARIO' => 'Hospitalario',
                                        'URGENCIAS' => 'Urgencias',
                                    ])
                                    ->required()
                                    ->default('AMBULATORIO')
                                    ->native(false),
                                    
                                Forms\Components\Select::make('admission_via')
                                    ->label('Ingreso Por')
                                    ->options([
                                        'URGENCIAS' => 'Urgencias',
                                        'CONSULTA_EXTERNA' => 'Consulta Externa',
                                        'HOSPITALIZACION' => 'Hospitalización',
                                        'REFERENCIA' => 'Referencia',
                                    ])
                                    ->required()
                                    ->default('CONSULTA_EXTERNA')
                                    ->native(false),
                            ]),
                            
                        Forms\Components\TextInput::make('service_area')
                            ->label('Área de Servicio')
                            ->maxLength(200),
                    ]),
                    
                Forms\Components\Section::make('Diagnóstico')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('diagnosis_code')
                                    ->label('Código Diagnóstico (CIE-10)')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('Ej: F32.0'),
                                    
                                Forms\Components\DateTimePicker::make('diagnosis_date')
                                    ->label('Fecha de Diagnóstico')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                            ]),
                            
                        Forms\Components\Textarea::make('diagnosis_description')
                            ->label('Descripción del Diagnóstico')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                            
                        Forms\Components\Select::make('diagnosis_type')
                            ->label('Tipo de Diagnóstico')
                            ->options([
                                'Diagnostico Principal' => 'Diagnóstico Principal',
                                'Diagnostico Relacionado' => 'Diagnóstico Relacionado',
                            ])
                            ->required()
                            ->default('Diagnostico Principal')
                            ->native(false),
                    ]),
                    
                Forms\Components\Section::make('Observaciones y Estado')
                    ->schema([
                        Forms\Components\Textarea::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->rows(4)
                            ->maxLength(1000),
                            
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'discharged' => 'Dado de Alta',
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('patient.document_number')
                    ->label('Documento')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('admission_date')
                    ->label('Fecha Ingreso')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('admission_type')
                    ->label('Tipo Ingreso')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('diagnosis_code')
                    ->label('Código')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('diagnosis_description')
                    ->label('Diagnóstico')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('status')
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
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'discharged' => 'Dado de Alta',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('admission_type')
                    ->label('Tipo de Ingreso')
                    ->options([
                        'AMBULATORIO' => 'Ambulatorio',
                        'HOSPITALARIO' => 'Hospitalario',
                        'URGENCIAS' => 'Urgencias',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('admission_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('admission_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('admission_date', '<=', $date));
                    }),
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
            'index' => Pages\ListMentalDisorders::route('/'),
            'create' => Pages\CreateMentalDisorder::route('/create'),
            'view' => Pages\ViewMentalDisorder::route('/{record}'),
            'edit' => Pages\EditMentalDisorder::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }
}