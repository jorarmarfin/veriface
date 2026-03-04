<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\RekognitionCollectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRekognitionCollection extends ViewRecord
{
    protected static string $resource = RekognitionCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

