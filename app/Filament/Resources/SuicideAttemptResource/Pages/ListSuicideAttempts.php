<?php

namespace App\Filament\Resources\SuicideAttemptResource\Pages;

use App\Filament\Resources\SuicideAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuicideAttempts extends ListRecords
{
    protected static string $resource = SuicideAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
