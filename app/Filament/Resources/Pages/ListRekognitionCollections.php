<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\RekognitionCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRekognitionCollections extends ListRecords
{
    protected static string $resource = RekognitionCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

