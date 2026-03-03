<?php

namespace App\Filament\Pages;

use App\Models\RekognitionCollection;
use App\Services\RekognitionService;
use Filament\Actions\Action;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CollectionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.collections-page';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;
    protected static ?string $title = 'Gestión de Colecciones';

    public bool $loading = false;

    private function getRekognition(): RekognitionService
    {
        return app(RekognitionService::class);
    }

    /**
     * Contar fotos en la carpeta storage/app/public/fotos
     */
    public function countPhotos(): int
    {
        try {
            $disk = Storage::disk('public');
            $files = $disk->files('fotos');

            // Filtrar solo archivos de imagen
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $photoCount = 0;

            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, $imageExtensions)) {
                    $photoCount++;
                }
            }

            return $photoCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Indexar fotos de la carpeta en la colección
     */
    public function indexPhotosFromFolder(): void
    {
        try {
            $photoCount = $this->countPhotos();

            if ($photoCount === 0) {
                Notification::make()
                    ->title('⚠️ Sin fotos')
                    ->body('No hay fotos en la carpeta storage/app/public/fotos')
                    ->warning()
                    ->send();
                return;
            }

            $disk = Storage::disk('public');
            $files = $disk->files('fotos');
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $indexed = 0;
            $failed = 0;

            $rekognition = $this->getRekognition();

            // Obtener la primera colección activa
            $collection = RekognitionCollection::where('is_active', true)->first();

            if (!$collection) {
                Notification::make()
                    ->title('❌ Error')
                    ->body('No hay colecciones activas. Crea una primero.')
                    ->danger()
                    ->send();
                return;
            }

            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                if (!in_array($extension, $imageExtensions)) {
                    continue;
                }

                try {
                    $imagePath = storage_path('app/public/' . $file);

                    if (file_exists($imagePath)) {
                        // Convertir imagen a base64
                        $imageData = base64_encode(file_get_contents($imagePath));

                        // Indexar la foto
                        $result = $rekognition->indexFace(
                            collectionId: $collection->collection_id,
                            externalImageId: basename($file),
                            imageData: $imageData,
                            isBase64: true
                        );

                        if ($result['success']) {
                            $indexed++;
                        } else {
                            $failed++;
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                }
            }

            // Actualizar contador de rostros en la colección
            $collection->increment('faces_count', $indexed);

            Notification::make()
                ->title('✅ Indexación completada')
                ->body("Se indexaron $indexed fotos correctamente. Fallidas: $failed")
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

    public function mount(): void
    {
        $this->syncCollectionsFromAWS();
    }

    /**
     * Sincronizar colecciones desde AWS con la base de datos
     */
    public function syncCollectionsFromAWS(): void
    {
        try {
            $this->loading = true;
            $rekognition = $this->getRekognition();
            $result = $rekognition->listCollections(maxResults: 100);

            if ($result['success']) {
                foreach ($result['collections'] ?? [] as $collection) {
                    $collectionId = $collection['collection_id'];
                    $details = $rekognition->describeCollection($collectionId);

                    RekognitionCollection::updateOrCreate(
                        ['collection_id' => $collectionId],
                        [
                            'name' => $collectionId,
                            'region' => config('aws.region', 'eu-north-1'),
                            'collection_arn' => $details['collection_arn'] ?? null,
                            'face_model_version' => $details['face_model_version'] ?? null,
                            'faces_count' => $details['face_count'] ?? 0,
                            'is_active' => true,
                        ]
                    );
                }

                Notification::make()
                    ->title('Colecciones sincronizadas')
                    ->body('Se sincronizaron las colecciones desde AWS')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error')
                    ->body($result['message'] ?? 'No se pudieron cargar las colecciones')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Crear nueva colección
     */
    public function createCollection(array $data): void
    {
        try {
            $rekognition = $this->getRekognition();
            $result = $rekognition->createCollection(
                collectionId: $data['collection_id'],
                description: $data['description'] ?? ''
            );

            if ($result['success']) {
                $details = $rekognition->describeCollection($data['collection_id']);

                RekognitionCollection::create([
                    'collection_id' => $data['collection_id'],
                    'name' => $data['collection_id'],
                    'region' => config('aws.region', 'eu-north-1'),
                    'collection_arn' => $details['collection_arn'] ?? null,
                    'face_model_version' => $details['face_model_version'] ?? null,
                    'faces_count' => 0,
                    'is_active' => true,
                ]);

                Notification::make()
                    ->title('✅ Colección creada')
                    ->body("Colección '{$data['collection_id']}' creada correctamente")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Error')
                    ->body($result['message'] ?? 'Error al crear la colección')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Eliminar colección
     */
    public function deleteCollection(string $collectionId): void
    {
        try {
            $rekognition = $this->getRekognition();
            $result = $rekognition->deleteCollection(collectionId: $collectionId);

            if ($result['success']) {
                RekognitionCollection::where('collection_id', $collectionId)->delete();

                Notification::make()
                    ->title('✅ Colección eliminada')
                    ->body("Colección '{$collectionId}' eliminada correctamente")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Error')
                    ->body($result['message'] ?? 'Error al eliminar la colección')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(RekognitionCollection::where('is_active', true))
            ->columns([
                TextColumn::make('collection_id')
                    ->label('ID de Colección')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-folder'),

                TextColumn::make('faces_count')
                    ->label('Rostros Indexados')
                    ->sortable()
                    ->badge()
                    ->color(function ($state) {
                        return match (true) {
                            $state === 0 => 'gray',
                            $state < 10 => 'info',
                            $state < 50 => 'warning',
                            default => 'success'
                        };
                    }),

                TextColumn::make('face_model_version')
                    ->label('Versión del Modelo')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detalles de la colección')
                    ->schema(fn ($record) => [
                        Section::make('Información')
                            ->schema([
                                TextInput::make('collection_id')
                                    ->label('ID')
                                    ->default($record->collection_id)
                                    ->disabled(),

                                TextInput::make('faces_count')
                                    ->label('Rostros')
                                    ->default($record->faces_count)
                                    ->disabled(),

                                TextInput::make('face_model_version')
                                    ->label('Versión Modelo')
                                    ->default($record->face_model_version)
                                    ->disabled(),

                                TextInput::make('collection_arn')
                                    ->label('ARN')
                                    ->default($record->collection_arn)
                                    ->disabled(),
                            ]),
                    ]),

                Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar colección?')
                    ->modalDescription('Esta acción es irreversible.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(fn ($record) => $this->deleteCollection($record->collection_id)),
            ]);
    }

    /**
     * Acciones de página con botón de crear colección
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('index-photos')
                ->label('📸 Indexar Fotos (' . $this->countPhotos() . ')')
                ->icon('heroicon-m-photo')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Indexar Fotos')
                ->modalDescription('Se indexarán todas las fotos de la carpeta storage/app/public/fotos en la primera colección activa')
                ->modalSubmitActionLabel('Sí, indexar')
                ->action(fn () => $this->indexPhotosFromFolder()),

            Action::make('create')
                ->label('Crear Colección')
                ->icon('heroicon-m-plus')
                ->color('success')
                ->modalHeading('Crear Nueva Colección')
                ->modalDescription('Crea una nueva colección para almacenar y gestionar rostros')
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('collection_id')
                                ->label('ID de la colección')
                                ->placeholder('ej: employees, visitors')
                                ->required()
                                ->regex('/^[a-z0-9-]*$/')
                                ->helperText('Solo letras minúsculas, números y guiones')
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('Descripción')
                                ->placeholder('Descripción opcional de la colección')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ])
                ->action(fn (array $data) => $this->createCollection($data)),
        ];
    }
}
