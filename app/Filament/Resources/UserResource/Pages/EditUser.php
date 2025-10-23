<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario actualizado')
            ->body('Los cambios han sido guardados exitosamente.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Limpiar el campo de contraseña antes de llenar el formulario
        unset($data['password']);
        
        return $data;
    }
}