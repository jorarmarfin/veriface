<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Imports\PeopleImport;
use App\Models\People;
use App\Models\RekognitionIndexedImage;
use App\Services\RekognitionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'people';
    protected static ?string $recordTitleAttribute = 'names';
    protected static ?string $title = 'Postulantes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Form fields if needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('names')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Número de Documento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('photo_path')
                    ->label('Ruta de Foto')
                    ->limit(50)
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('upload_emergency_photo')
                    ->label('Subir Foto')
                    ->icon('heroicon-m-camera')
                    ->color('info')
                    ->modalHeading('Subir foto de emergencia')
                    ->modalDescription('La foto se guardará en la carpeta de la institución y se actualizará el registro de la persona.')
                    ->schema([
                        FileUpload::make('photo')
                            ->label('Foto')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->image()
                            ->disk('public')
                            ->directory(fn () => trim((string) $this->ownerRecord->filepath, '/'))
                            ->visibility('public')
                            ->preserveFilenames()
                            ->maxSize(5120)
                            ->required(),
                    ])
                    ->action(function (People $record, array $data): void {
                        $this->uploadEmergencyPhoto($record, $data['photo'] ?? null);
                    }),

                Action::make('index_individual_photo')
                    ->label('Indexar Foto')
                    ->icon('heroicon-m-finger-print')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Indexar foto individual')
                    ->modalDescription('Se indexará solo la foto de esta persona en la colección Rekognition de la institución.')
                    ->modalSubmitActionLabel('Sí, indexar')
                    ->action(function (People $record): void {
                        $this->indexIndividualPhoto($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('download_template')
                    ->label('Descargar Template')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->url('/plantilla_postulantes.xlsx')
                    ->openUrlInNewTab(),
                Action::make('import_people')
                    ->label('Importar Postulantes')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->color('success')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo Excel (.xlsx, .csv)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->helperText('Máximo 10MB. Formatos permitidos: .xlsx, .csv'),
                    ])
                    ->action(function (array $data) {
                        $this->importPeople($data['file']);
                    }),
            ]);
    }

    protected function importPeople(string $filePath): void
    {
        try {
            $import = new PeopleImport($this->ownerRecord->id);
            Excel::import($import, storage_path('app/private/' . $filePath));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            if ($imported > 0) {
                Notification::make()
                    ->title('✅ Importación Completada')
                    ->body("Se importaron $imported postulantes. Se omitieron $skipped registros.")
                    ->success()
                    ->send();
            }

            if (!empty($errors)) {
                Notification::make()
                    ->title('⚠️ Errores en la Importación')
                    ->body(implode('<br>', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '<br>... y ' . (count($errors) - 5) . ' errores más.' : ''))
                    ->warning()
                    ->send();
            }

            if ($imported === 0 && empty($errors)) {
                Notification::make()
                    ->title('ℹ️ Importación Finalizada')
                    ->body('No se importaron nuevos registros.')
                    ->info()
                    ->send();
            }

            $this->dispatch('refresh');
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error en la Importación')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function uploadEmergencyPhoto(People $record, mixed $uploadedPhoto): void
    {
        try {
            if (empty($this->ownerRecord->filepath)) {
                Notification::make()
                    ->title('❌ Sin carpeta')
                    ->body('La institución no tiene filepath configurado.')
                    ->danger()
                    ->send();
                return;
            }

            $photoPath = is_array($uploadedPhoto) ? ($uploadedPhoto[0] ?? null) : $uploadedPhoto;

            if (empty($photoPath) || !Storage::disk('public')->exists($photoPath)) {
                Notification::make()
                    ->title('❌ Archivo no encontrado')
                    ->body('No se pudo guardar la foto en storage/app/public.')
                    ->danger()
                    ->send();
                return;
            }

            $record->update([
                'photo_path' => ltrim((string) $photoPath, '/'),
            ]);

            Notification::make()
                ->title('✅ Foto actualizada')
                ->body("Se actualizó la foto de {$record->names}. Ahora puedes indexarla individualmente.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al subir foto')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function indexIndividualPhoto(People $record): void
    {
        try {
            if (empty($record->photo_path)) {
                Notification::make()
                    ->title('⚠️ Sin foto')
                    ->body('La persona no tiene photo_path asignado.')
                    ->warning()
                    ->send();
                return;
            }

            $institution = $this->ownerRecord;

            if (empty($institution->rekognition_collection_id)) {
                Notification::make()
                    ->title('⚠️ Sin colección')
                    ->body('La institución no tiene colección de Rekognition asignada.')
                    ->warning()
                    ->send();
                return;
            }

            $collection = $institution->rekognitionCollection;
            if (!$collection || !$collection->is_active) {
                Notification::make()
                    ->title('⚠️ Colección inválida')
                    ->body('La colección de Rekognition no existe o está inactiva.')
                    ->warning()
                    ->send();
                return;
            }

            if (!Storage::disk('public')->exists($record->photo_path)) {
                Notification::make()
                    ->title('❌ Foto no encontrada')
                    ->body("No existe el archivo: {$record->photo_path}")
                    ->danger()
                    ->send();
                return;
            }

            $fileContent = Storage::disk('public')->get($record->photo_path);
            $imageData = base64_encode($fileContent);

            $rekognition = app(RekognitionService::class);
            $result = $rekognition->indexFace(
                collectionId: $collection->collection_id,
                externalImageId: "{$record->document_number}.jpg",
                imageData: $imageData,
                isBase64: true,
                userAttributes: [
                    'person_id' => $record->id,
                    'document_number' => $record->document_number,
                    'institution_id' => $institution->id,
                ]
            );

            if (!($result['success'] ?? false)) {
                Notification::make()
                    ->title('❌ Error al indexar')
                    ->body($result['message'] ?? 'Rekognition no pudo indexar la foto.')
                    ->danger()
                    ->send();
                return;
            }

            $faceIds = array_values(array_filter($result['face_ids'] ?? []));
            $faceRecords = $result['face_details'] ?? [];

            $saved = 0;
            foreach ($faceIds as $index => $faceId) {
                if (RekognitionIndexedImage::where('face_id', $faceId)->exists()) {
                    continue;
                }

                $faceRecord = $faceRecords[$index] ?? [];
                $confidence = data_get($faceRecord, 'Face.Confidence');

                RekognitionIndexedImage::create([
                    'uuid' => Str::uuid(),
                    'rekognition_collection_id' => $collection->id,
                    'person_id' => $record->id,
                    'face_id' => $faceId,
                    'image_path' => $record->photo_path,
                    'image_name' => basename((string) $record->photo_path),
                    'confidence' => $confidence,
                    'face_details' => $faceRecord,
                    'is_active' => true,
                    'indexed_at' => now(),
                ]);

                $saved++;
            }

            $details = $rekognition->describeCollection($collection->collection_id);
            if ($details['success'] ?? false) {
                $collection->update([
                    'faces_count' => (int) ($details['face_count'] ?? $collection->faces_count),
                ]);
            }

            Notification::make()
                ->title('✅ Foto indexada')
                ->body("Indexación completada para {$record->names}. Rostros nuevos registrados: {$saved}.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
