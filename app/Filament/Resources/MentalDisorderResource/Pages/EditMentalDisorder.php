<?php

namespace App\Filament\Resources\MentalDisorderResource\Pages;

use App\Filament\Resources\MentalDisorderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMentalDisorder extends EditRecord
{
    protected static string $resource = MentalDisorderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
