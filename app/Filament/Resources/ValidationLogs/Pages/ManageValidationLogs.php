<?php

namespace App\Filament\Resources\ValidationLogs\Pages;

use App\Filament\Resources\ValidationLogs\ValidationLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageValidationLogs extends ManageRecords
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
