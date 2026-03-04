<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use App\Models\RekognitionCollection;

class SyncIndexedImages extends Command
{
    protected $signature = 'rekognition:sync-indexed-images {--collection= : ID de colección específica (opcional)}';

    protected $description = 'Sincronizar imágenes indexadas desde AWS Rekognition con la base de datos';

    public function handle()
    {
        try {
            $rekognition = app(RekognitionService::class);

            if ($collectionId = $this->option('collection')) {
                $collections = RekognitionCollection::where('collection_id', $collectionId)->get();
            } else {
                $collections = RekognitionCollection::where('is_active', true)->get();
            }

            $totalSynced = 0;

            foreach ($collections as $collection) {
                try {
                    $this->info("Sincronizando colección: {$collection->collection_id}");

                    $result = $rekognition->listFaces($collection->collection_id, maxResults: 1000);

                    if ($result['success']) {
                        $syncedCount = 0;
                        foreach ($result['faces'] ?? [] as $face) {
                            $existingImage = \App\Models\RekognitionIndexedImage::where(
                                'face_id',
                                $face['FaceId']
                            )->first();

                            if (!$existingImage) {
                                \App\Models\RekognitionIndexedImage::create([
                                    'uuid' => \Illuminate\Support\Str::uuid(),
                                    'rekognition_collection_id' => $collection->id,
                                    'person_id' => null,
                                    'face_id' => $face['FaceId'],
                                    'image_path' => $face['ExternalImageId'] ?? '',
                                    'image_name' => basename($face['ExternalImageId'] ?? ''),
                                    'confidence' => $face['Confidence'] ?? null,
                                    'face_details' => json_encode($face),
                                    'is_active' => true,
                                    'indexed_at' => now(),
                                ]);
                                $syncedCount++;
                            }
                        }

                        $this->info("✅ {$collection->collection_id}: Se sincronizaron {$syncedCount} imágenes (Total: {$result['face_count']})");
                        $totalSynced += $syncedCount;
                    } else {
                        $this->error("❌ {$collection->collection_id}: {$result['message']}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error sincronizando {$collection->collection_id}: {$e->getMessage()}");
                }
            }

            $this->info("\n✅ Sincronización completada. Total de imágenes nuevas: {$totalSynced}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}

