<?php

// ================================
// ARCHIVO: app/Filament/Resources/PatientResource/Pages/CreatePatient.php
// ================================

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Paciente registrado exitosamente';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        // $data['assigned_at'] = now();
        
        return $data;
    }
}