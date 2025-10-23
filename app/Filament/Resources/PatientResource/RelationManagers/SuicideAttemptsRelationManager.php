<?php

// ================================
// ARCHIVO: app/Filament/Resources/PatientResource/RelationManagers/SuicideAttemptsRelationManager.php
// ================================

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SuicideAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'suicideAttempts';
    
    protected static ?string $title = 'Intentos de Suicidio';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('event_date')
                    ->label('Fecha del Evento')
                    ->required(),
                Forms\Components\TextInput::make('attempt_number')
                    ->label('Número de Intento')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\Textarea::make('mechanism')
                    ->label('Mecanismo Utilizado')
                    ->rows(3),
                Forms\Components\Textarea::make('trigger_factor')
                    ->label('Factor Desencadenante')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Fecha Evento')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('N° Intento')
                    ->badge(),
                Tables\Columns\TextColumn::make('mechanism')
                    ->label('Mecanismo')
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'danger',
                        'inactive' => 'warning',
                        'resolved' => 'success',
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