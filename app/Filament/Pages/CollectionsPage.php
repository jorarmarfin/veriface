<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\CollectionsForm;
use App\Filament\Pages\Tables\CollectionsTable;
use App\Services\RekognitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CollectionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.collections-page';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;
    protected static ?string $title = 'Gestión de Colecciones';

    private RekognitionService $rekognition;
    public array $collections = [];
    public bool $loading = false;

    public function mount(): void
    {
        $this->rekognition = app(RekognitionService::class);
        $this->loadCollections();
    }

    /**
     * Cargar colecciones desde AWS
     */
    public function loadCollections(): void
    {
        try {
            $this->loading = true;
            $result = $this->rekognition->listCollections(maxResults: 100);

            if ($result['success']) {
                $this->collections = array_map(function ($collection) {
                    $details = $this->rekognition->describeCollection($collection['collection_id']);
                    return [
                        'id' => $collection['collection_id'],
                        'name' => $collection['collection_id'],
                        'face_count' => $details['face_count'] ?? 0,
                        'created_at' => $details['creation_timestamp'] ?? null,
                        'arn' => $details['collection_arn'] ?? null,
                        'face_model_version' => $details['face_model_version'] ?? 'N/A',
                    ];
                }, $result['collections'] ?? []);

                Notification::make()
                    ->title('Colecciones cargadas')
                    ->body('Se cargaron ' . count($this->collections) . ' colecciones correctamente')
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
            $result = $this->rekognition->createCollection(
                collectionId: $data['collection_id'],
                description: $data['description'] ?? ''
            );

            if ($result['success']) {
                Notification::make()
                    ->title('✅ Colección creada')
                    ->body("Colección '{$data['collection_id']}' creada correctamente")
                    ->success()
                    ->send();

                $this->loadCollections();
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
            $result = $this->rekognition->deleteCollection(collectionId: $collectionId);

            if ($result['success']) {
                Notification::make()
                    ->title('✅ Colección eliminada')
                    ->body("Colección '{$collectionId}' eliminada correctamente")
                    ->success()
                    ->send();

                $this->loadCollections();
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
        return CollectionsTable::configure($table, $this->collections);
    }

    /**
     * Acciones de página con botón de crear colección
     */
    protected function getHeaderActions(): array
    {
        return [
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

            Action::make('refresh')
                ->label('Refrescar')
                ->icon('heroicon-m-arrow-path')
                ->color('info')
                ->action(fn () => $this->loadCollections()),
        ];
    }
}
