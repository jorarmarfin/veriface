<?php

namespace App\Filament\Resources\Institutions;

use App\Filament\Resources\Institutions\Pages\ManageInstitutions;
use App\Filament\Resources\Institutions\Pages\CreateInstitutions;
use App\Filament\Resources\Institutions\Pages\EditInstitutions;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;
    protected static ?string $modelLabel = 'Institución';
    protected static ?string $pluralModelLabel = 'Instituciones';


    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la Institución')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $slug = \Str::slug($state);
                        $set('slug', $slug);
                        $set('filepath', $slug);
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('URL Amigable (Slug)')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Se genera automáticamente desde el nombre')
                    ->columnSpanFull(),

                TextInput::make('filepath')
                    ->label('Ruta de la Carpeta')
                    ->helperText('Ruta donde se guardarán las fotos (se creará en storage/app/public/)')
                    ->placeholder('ej: universidad-central')
                    ->required()
                    ->columnSpanFull(),

                Select::make('rekognition_collection_id')
                    ->label('Colección de Rekognition')
                    ->options(function () {
                        return \App\Models\RekognitionCollection::pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->inline(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rekognitionCollection.name')
                    ->label('Colección Rekognition')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filepath')
                    ->label('Carpeta')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Directorio: storage/app/public/'),
                TextColumn::make('photo_count')
                    ->label('📸 Fotos')
                    ->state(function (Institution $record): string {
                        if (empty($record->filepath)) {
                            return '0';
                        }

                        $basePath = storage_path('app/public/' . $record->filepath);

                        if (!is_dir($basePath)) {
                            return '0';
                        }

                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $files = scandir($basePath);
                        $photoCount = 0;

                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..') continue;

                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (in_array($extension, $imageExtensions)) {
                                $photoCount++;
                            }
                        }

                        return (string) $photoCount;
                    })
                    ->alignment('center')
                    ->sortable(false),
                TextColumn::make('people_count')
                    ->label('👥 Postulantes')
                    ->state(function (Institution $record): string {
                        return (string) $record->people()->count();
                    })
                    ->alignment('center')
                    ->sortable(false)
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('index-photos')
                    ->label('Indexar Fotos')
                    ->icon('heroicon-m-photo')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Indexar fotos de la institución')
                    ->modalDescription(fn(Institution $record) => 'Se indexarán todas las fotos de la carpeta: ' . $record->filepath)
                    ->modalSubmitActionLabel('Sí, indexar')
                    ->action(function (Institution $record) {
                        self::indexInstitutionPhotos($record);
                    }),
                Action::make('url_validation')
                    ->label('Validación')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->openUrlInNewTab()
                    ->url(fn(Institution $record) => route('validate', ['uuid' => $record->uuid])),
                EditAction::make()
                    ->url(fn(Institution $record) => static::getUrl('edit', ['record' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstitutions::route('/'),
            'create' => CreateInstitutions::route('/create'),
            'edit' => EditInstitutions::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            Pages\PeopleRelationManager::class,
        ];
    }

    /**
     * Indexar fotos de una institución en su colección Rekognition
     */
    public static function indexInstitutionPhotos(Institution $record): void
    {
        try {
            // Validar que tenga una colección asignada
            if (empty($record->rekognition_collection_id)) {
                Notification::make()
                    ->title('⚠️ Sin colección')
                    ->body('La institución no tiene una colección de Rekognition asignada')
                    ->warning()
                    ->send();
                return;
            }

            // Obtener la colección
            $collection = $record->rekognitionCollection;
            if (!$collection || !$collection->is_active) {
                Notification::make()
                    ->title('⚠️ Colección inactiva')
                    ->body('La colección asignada no está activa')
                    ->warning()
                    ->send();
                return;
            }

            // Validar que tenga una ruta de archivos
            if (empty($record->filepath)) {
                Notification::make()
                    ->title('⚠️ Sin ruta')
                    ->body('La institución no tiene una ruta de archivos definida')
                    ->warning()
                    ->send();
                return;
            }

            // Obtener la ruta completa
            $basePath = storage_path('app/public/' . $record->filepath);

            if (!is_dir($basePath)) {
                Notification::make()
                    ->title('❌ Carpeta no existe')
                    ->body('La carpeta ' . $record->filepath . ' no existe en el servidor')
                    ->danger()
                    ->send();
                return;
            }

            // Obtener las imágenes
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $files = scandir($basePath);
            $imageFiles = [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $filePath = $basePath . '/' . $file;
                if (is_file($filePath)) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $imageExtensions)) {
                        $imageFiles[] = $filePath;
                    }
                }
            }

            if (empty($imageFiles)) {
                Notification::make()
                    ->title('ℹ️ Sin fotos')
                    ->body('No se encontraron fotos en la carpeta ' . $record->filepath)
                    ->info()
                    ->send();
                return;
            }

            // Indexar cada imagen
            $rekognition = app(\App\Services\RekognitionService::class);
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($imageFiles as $filePath) {
                $fileName = basename($filePath);

                try {
                    // Convertir a base64
                    $imageData = base64_encode(file_get_contents($filePath));

                    // Indexar en Rekognition
                    $result = $rekognition->indexFace(
                        collectionId: $collection->collection_id,
                        externalImageId: $fileName,
                        imageData: $imageData,
                        isBase64: true,
                        userAttributes: [
                            'institution_id' => $record->id,
                            'institution_name' => $record->name,
                        ]
                    );

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $fileName . ': ' . ($result['message'] ?? 'Error desconocido');
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = $fileName . ': ' . $e->getMessage();
                }
            }

            // Mostrar resultado
            $message = "✅ Indexadas: $successCount";
            if ($errorCount > 0) {
                $message .= " | ❌ Errores: $errorCount";
            }

            Notification::make()
                ->title('Indexación completada')
                ->body($message)
                ->success()
                ->send();

            // Log de errores si los hay
            if (!empty($errors)) {
                \Log::warning('Errores durante indexación de fotos', [
                    'institution_id' => $record->id,
                    'institution_name' => $record->name,
                    'collection_id' => $collection->collection_id,
                    'errors' => $errors,
                ]);
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            \Log::error('Error indexando fotos de institución', [
                'institution_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
