<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Usuario')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->icon('heroicon-o-users')
                ->badge(fn () => \App\Models\User::count()),
            
            'active' => Tab::make('Activos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\User::where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            
            'inactive' => Tab::make('Inactivos')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\User::where('is_active', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            
            'super_admin' => Tab::make('Super Admins')
                ->icon('heroicon-o-shield-check')
                ->badge(fn () => \App\Models\User::role('super_admin')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->role('super_admin')),
            
            'panel_user' => Tab::make('Panel Users')
                ->icon('heroicon-o-user-circle')
                ->badge(fn () => \App\Models\User::role('panel_user')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->role('panel_user')),
        ];
    }
}