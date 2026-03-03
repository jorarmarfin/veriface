<?php

namespace App\Filament\Pages;

use App\Services\RekognitionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CollectionsPage extends Page
{
    protected string $view = 'filament.pages.collections-page';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;
    protected static ?string $title = 'Gestión de Colecciones';
    public RekognitionService $rekognition;

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
                // Obtener detalles de cada colección
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
        }
    }


}
