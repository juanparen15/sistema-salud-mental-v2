<?php
// ================================
// ARCHIVO: app/Filament/Resources/PatientResource/RelationManagers/MentalDisordersRelationManager.php
// ================================

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MentalDisordersRelationManager extends RelationManager
{
    protected static string $relationship = 'mentalDisorders';
    
    protected static ?string $title = 'Trastornos Mentales';
    
    protected static ?string $recordTitleAttribute = 'diagnosis_description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('admission_date')
                    ->label('Fecha de Ingreso')
                    ->required(),
                Forms\Components\Select::make('admission_type')
                    ->label('Tipo de Ingreso')
                    ->options([
                        'AMBULATORIO' => 'Ambulatorio',
                        'HOSPITALARIO' => 'Hospitalario',
                        'URGENCIAS' => 'Urgencias',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('diagnosis_code')
                    ->label('Código Diagnóstico')
                    ->required(),
                Forms\Components\Textarea::make('diagnosis_description')
                    ->label('Descripción Diagnóstico')
                    ->required()
                    ->rows(3),
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
                Tables\Columns\TextColumn::make('diagnosis_code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('diagnosis_description')
                    ->label('Diagnóstico')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'discharged' => 'danger',
                    }),
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