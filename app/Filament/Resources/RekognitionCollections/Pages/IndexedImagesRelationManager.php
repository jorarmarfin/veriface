<?php

namespace App\Filament\Resources\RekognitionCollections\Pages;

use App\Models\RekognitionIndexedImage;
use App\Services\RekognitionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                    ->sortable()
                    ->placeholder('Sin asignar')
                    ->default('Sin asignar'),

                TextColumn::make('face_id')
                    ->label('ID Rostro')
                    ->limit(15)
                    ->copyable()
                    ->tooltip('Click para copiar'),

                TextColumn::make('confidence')
                    ->label('Confianza')
                    ->suffix('%')
                    ->alignment('center')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activa')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('indexed_at')
                    ->label('Indexada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('toggle-active')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-m-x-mark' : 'heroicon-m-check')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title('✅ Actualizado')
                            ->body($record->is_active ? 'Imagen activada' : 'Imagen desactivada')
                            ->success()
                            ->send();
                    }),

                Action::make('delete-from-aws')
                    ->label('Eliminar')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar imagen indexada')
                    ->modalDescription('Se eliminará la imagen de AWS Rekognition y de la base de datos. Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(function ($record) {
                        $this->deleteIndexedImage($record);
                    }),
            ]);
    }

    /**
     * Eliminar una imagen indexada de AWS y de la base de datos
     */
    private function deleteIndexedImage(RekognitionIndexedImage $record): void
    {
        try {
            $rekognition = app(RekognitionService::class);

            // Obtener la colección
            $collection = $record->collection;

            if (!$collection) {
                \Filament\Notifications\Notification::make()
                    ->title('❌ Error')
                    ->body('No se encontró la colección asociada')
                    ->danger()
                    ->send();
                return;
            }

            // Eliminar de AWS Rekognition
            $result = $rekognition->deleteFaces($collection->collection_id, [$record->face_id]);

            if ($result['success']) {
                // Eliminar de la base de datos
                $record->delete();

                // Actualizar contador de rostros en la colección
                $collection->decrement('faces_count');

                \Filament\Notifications\Notification::make()
                    ->title('✅ Imagen eliminada')
                    ->body('La imagen ha sido eliminada de AWS y de la base de datos')
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('❌ Error en AWS')
                    ->body('No se pudo eliminar de AWS Rekognition: ' . ($result['message'] ?? 'Error desconocido'))
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            \Log::error('Error eliminando imagen indexada', [
                'image_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

