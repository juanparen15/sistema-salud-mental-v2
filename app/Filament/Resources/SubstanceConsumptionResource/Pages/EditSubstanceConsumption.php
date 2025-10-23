<?php

namespace App\Filament\Resources\SubstanceConsumptionResource\Pages;

use App\Filament\Resources\SubstanceConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubstanceConsumption extends EditRecord
{
    protected static string $resource = SubstanceConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
