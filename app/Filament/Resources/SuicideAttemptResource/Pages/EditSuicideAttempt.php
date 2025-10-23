<?php

namespace App\Filament\Resources\SuicideAttemptResource\Pages;

use App\Filament\Resources\SuicideAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuicideAttempt extends EditRecord
{
    protected static string $resource = SuicideAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
