<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Usuarios';
    
    protected static ?string $modelLabel = 'Usuario';
    
    protected static ?string $pluralModelLabel = 'Usuarios';
    
    protected static ?string $navigationGroup = 'Administración';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('Ej: Juan Pérez'),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('usuario@ejemplo.com'),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->placeholder('Mínimo 8 caracteres')
                            ->helperText('Dejar en blanco para mantener la contraseña actual'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->same('password')
                            ->dehydrated(false)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->placeholder('Repetir contraseña')
                            ->visible(fn ($context) => $context === 'create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->helperText('Selecciona uno o más roles para el usuario')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->default(true)
                            ->helperText('Los usuarios inactivos no pueden iniciar sesión')
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Ej: +57 300 123 4567'),

                        Forms\Components\TextInput::make('position')
                            ->label('Cargo')
                            ->maxLength(100)
                            ->placeholder('Ej: Psicólogo, Médico, Coordinador'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Información adicional sobre el usuario')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Auditoría')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Fecha de Creación')
                            ->content(fn ($record): string => $record?->created_at ? $record->created_at->format('d/m/Y H:i:s') : '-'),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Última Actualización')
                            ->content(fn ($record): string => $record?->updated_at ? $record->updated_at->format('d/m/Y H:i:s') : '-'),

                        Forms\Components\Placeholder::make('email_verified_at')
                            ->label('Email Verificado')
                            ->content(fn ($record): string => $record?->email_verified_at ? '✅ ' . $record->email_verified_at->format('d/m/Y H:i:s') : '❌ No verificado'),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->visible(fn ($context) => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copiado')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'panel_user' => 'success',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon('heroicon-o-phone')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Cargo')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verificado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verificado')
                    ->placeholder('Todos')
                    ->trueLabel('Verificados')
                    ->falseLabel('No Verificados')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    )
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Creado desde')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Creado hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (User $record) => $record->update(['is_active' => false]))
                        ->visible(fn (User $record): bool => $record->is_active),
                    Tables\Actions\Action::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (User $record) => $record->update(['is_active' => true]))
                        ->visible(fn (User $record): bool => !$record->is_active),
                    Tables\Actions\Action::make('reset_password')
                        ->label('Resetear Contraseña')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('Nueva Contraseña')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            Forms\Components\TextInput::make('new_password_confirmation')
                                ->label('Confirmar Contraseña')
                                ->password()
                                ->required(),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'password' => Hash::make($data['new_password'])
                            ]);
                        })
                        ->successNotificationTitle('Contraseña actualizada'),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar Seleccionados')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription('Crea el primer usuario haciendo clic en el botón de arriba')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes();
    }
}