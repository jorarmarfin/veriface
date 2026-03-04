<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Models\RekognitionIndexedImage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class IndexedImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'indexedImages';

    protected static ?string $recordTitleAttribute = 'image_name';

    protected static ?string $title = 'Imágenes Indexadas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('image_name')
                    ->label('Imagen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('person.names')
                    ->label('Persona')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('face_id')
                    ->label('ID Rostro')
                    ->limit(20)
                    ->copyable(),

                TextColumn::make('confidence')
                    ->label('Confianza')
                    ->suffix('%')
                    ->alignment('center'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activa'),

                TextColumn::make('indexed_at')
                    ->label('Indexada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

