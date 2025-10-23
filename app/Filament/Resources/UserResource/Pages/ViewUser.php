<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información Personal')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre Completo')
                            ->icon('heroicon-o-user')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Correo Electrónico')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->copyMessage('Email copiado'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('Teléfono')
                            ->icon('heroicon-o-phone')
                            ->placeholder('No especificado'),

                        Infolists\Components\TextEntry::make('position')
                            ->label('Cargo')
                            ->icon('heroicon-o-briefcase')
                            ->placeholder('No especificado'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Roles Asignados')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                            ->color(fn (string $state): string => match ($state) {
                                'super_admin' => 'danger',
                                'panel_user' => 'success',
                                default => 'primary',
                            })
                            ->icon('heroicon-o-shield-check')
                            ->columnSpanFull(),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Estado del Usuario')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('permissions_count')
                            ->label('Total de Permisos')
                            ->getStateUsing(fn ($record): int => $record->getAllPermissions()->count())
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-key'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Permisos Directos')
                    ->schema([
                        Infolists\Components\TextEntry::make('permissions.name')
                            ->label('Permisos Asignados Directamente')
                            ->badge()
                            ->color('warning')
                            ->placeholder('Sin permisos directos')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record): bool => $record->permissions->isNotEmpty()),

                Infolists\Components\Section::make('Información Adicional')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('Sin notas adicionales')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record): bool => filled($record->notes)),

                Infolists\Components\Section::make('Verificación y Auditoría')
                    ->schema([
                        Infolists\Components\IconEntry::make('email_verified_at')
                            ->label('Email Verificado')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('warning'),

                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('Fecha de Verificación')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('No verificado')
                            ->visible(fn ($record): bool => filled($record->email_verified_at)),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i:s')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i:s')
                            ->icon('heroicon-o-clock')
                            ->since()
                            ->tooltip(fn ($record): string => $record->updated_at->format('d/m/Y H:i:s')),
                    ])
                    ->columns(2),
            ]);
    }
}