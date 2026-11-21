<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\People;
use App\Models\RekognitionIndexedImage;
use App\Models\ValidationLog;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de validación facial compartida por la web pública y la API de clientes.
 */
class ValidateFace
{
    public function __construct(protected RekognitionService $rekognition)
    {
    }

    /**
     * @return array{status:int, payload:array}
     */
    public function handle(Institution $institution, ?string $imageData): array
    {
        if (empty($imageData)) {
            return $this->result(400, ['success' => false, 'message' => 'No se recibió imagen']);
        }

        if (!$institution->is_active) {
            return $this->result(403, [
                'success' => false,
                'message' => 'La institución está desactivada',
                'type' => 'inactive',
            ]);
        }

        if ($institution->isValidationQuotaExceeded()) {
            return $this->quotaExceeded($institution);
        }

        if (!$institution->rekognition_collection_id) {
            return $this->result(400, [
                'success' => false,
                'message' => 'La institución no tiene una colección de Rekognition configurada',
            ]);
        }

        $collection = $institution->rekognitionCollection;
        if (!$collection || !$collection->collection_id) {
            return $this->result(400, [
                'success' => false,
                'message' => 'Colección de Rekognition no encontrada',
            ]);
        }

        // Consume una validación de forma atómica para no sobrepasar el límite en concurrencia
        if (!$this->consumeValidationAttempt($institution)) {
            $institution->refresh();

            return $this->quotaExceeded($institution);
        }

        if (str_starts_with($imageData, 'data:image')) {
            $imageData = explode(',', $imageData, 2)[1] ?? '';
        }

        try {
            $searchResult = $this->rekognition->searchFacesByImage(
                collectionId: $collection->collection_id,
                imageData: $imageData,
                isBase64: true,
                faceMatchThreshold: config('services.face.match_threshold'),
                maxFaces: 5
            );
        } catch (\Throwable $e) {
            // Fallo de AWS: no se cobra la validación al cliente
            $this->refundValidationAttempt($institution);
            throw $e;
        }

        if (!$searchResult['success']) {
            // Error de AWS/imagen no procesable: se devuelve la validación consumida
            $this->refundValidationAttempt($institution);

            $payload = [
                'success' => false,
                'message' => $searchResult['message'] ?? 'Error en el análisis de rostro',
            ];
            $this->log($institution, null, null, false, $payload);

            return $this->result(400, $payload);
        }

        $matches = $searchResult['matches'] ?? [];

        if (empty($matches)) {
            $payload = [
                'success' => false,
                'message' => 'No se encontró coincidencia con ningún registro',
                'type' => 'no_match',
            ];
            $this->log($institution, null, null, false, $payload);

            return $this->result(200, $payload);
        }

        $resolved = $this->resolveMatchedPerson(
            matches: $matches,
            institution: $institution,
            rekognitionCollectionDbId: $collection->id ?? null
        );

        if (!$resolved) {
            $bestMatch = $matches[0] ?? [];
            $similarity = $bestMatch['similarity'] ?? 0;
            $externalImageIdRaw = $bestMatch['external_image_id'] ?? null;
            $faceId = $bestMatch['face_id'] ?? null;
            $documentNumber = $externalImageIdRaw ? pathinfo((string) $externalImageIdRaw, PATHINFO_FILENAME) : null;

            Log::warning('Validación sin mapeo de persona pese a coincidencias en Rekognition', [
                'institution_id' => $institution->id,
                'collection_id' => $collection->collection_id ?? null,
                'matches_count' => count($matches),
                'first_match' => $bestMatch,
            ]);

            $payload = [
                'success' => false,
                'message' => 'Coincidencia sin mapeo de persona en base de datos',
                'type' => 'no_match',
                'data' => [
                    'document_number' => $documentNumber,
                    'similarity' => round((float) $similarity, 2),
                    'face_id' => $faceId,
                    'external_image_id' => $externalImageIdRaw,
                ],
            ];
            $this->log($institution, $documentNumber, $similarity, false, $payload);

            return $this->result(200, $payload);
        }

        $bestMatch = $resolved['match'];
        /** @var People $person */
        $person = $resolved['person'];
        $resolvedBy = $resolved['resolved_by'] ?? 'unknown';
        $similarity = $bestMatch['similarity'] ?? 0;

        Log::info('Validación resuelta con persona mapeada', [
            'institution_id' => $institution->id,
            'collection_id' => $collection->collection_id ?? null,
            'person_id' => $person->id,
            'document_number' => $person->document_number,
            'similarity' => $similarity,
            'face_id' => $bestMatch['face_id'] ?? null,
            'external_image_id' => $bestMatch['external_image_id'] ?? null,
            'resolved_by' => $resolvedBy,
        ]);

        $payload = [
            'success' => true,
            'message' => 'Persona validada correctamente',
            'type' => 'match',
            'data' => [
                'names' => $person->names,
                'document_number' => $person->document_number,
                'institution' => $institution->name,
                'event' => $institution->event,
                'similarity' => round($similarity, 2),
                'photo_url' => $person->photo_path ? asset('storage/' . $person->photo_path) : null,
                'created_at' => $person->created_at->format('Y-m-d'),
                'metadata' => $person->metadata,
                'resolved_by' => $resolvedBy,
            ],
        ];
        $this->log($institution, $person->document_number, $similarity, true, $payload);

        return $this->result(200, $payload);
    }

    private function refundValidationAttempt(Institution $institution): void
    {
        Institution::whereKey($institution->id)
            ->where('validations_used', '>', 0)
            ->decrement('validations_used');
    }

    private function quotaExceeded(Institution $institution): array
    {
        return $this->result(403, [
            'success' => false,
            'message' => 'La institución alcanzó el límite de validaciones contratadas',
            'type' => 'quota_exceeded',
            'data' => [
                'validations_contracted' => $institution->validations_contracted,
                'validations_used' => $institution->validations_used,
                'validations_remaining' => $institution->validations_remaining,
            ],
        ]);
    }

    private function log(Institution $institution, ?string $document, $similarity, bool $matched, array $payload): void
    {
        ValidationLog::create([
            'institution_id' => $institution->id,
            'document_number' => $document,
            'similarity' => $similarity,
            'matched' => $matched,
            'validated_at' => now(),
            'response' => $payload,
        ]);
    }

    private function result(int $status, array $payload): array
    {
        return ['status' => $status, 'payload' => $payload];
    }

    /**
     * Si hay límite, solo incrementa cuando validations_used < validations_contracted.
     */
    private function consumeValidationAttempt(Institution $institution): bool
    {
        if ($institution->validations_contracted === null) {
            Institution::whereKey($institution->id)->increment('validations_used');

            return true;
        }

        return Institution::query()
            ->whereKey($institution->id)
            ->whereColumn('validations_used', '<', 'validations_contracted')
            ->increment('validations_used') > 0;
    }

    /**
     * Resuelve la persona a partir de los matches de Rekognition.
     * Prioriza el vínculo por face_id (rekognition_indexed_images), y usa external_image_id como fallback.
     */
    private function resolveMatchedPerson(array $matches, Institution $institution, ?int $rekognitionCollectionDbId = null): ?array
    {
        foreach ($matches as $match) {
            $faceId = $match['face_id'] ?? null;
            $externalImageIdRaw = $match['external_image_id'] ?? null;
            $personByFace = null;
            $personByExternal = null;

            if ($faceId) {
                $indexedQuery = RekognitionIndexedImage::query()
                    ->with('person')
                    ->where('face_id', $faceId);

                if ($rekognitionCollectionDbId) {
                    $indexedQuery->where('rekognition_collection_id', $rekognitionCollectionDbId);
                }

                $indexedImage = $indexedQuery
                    ->orderByDesc('indexed_at')
                    ->orderByDesc('id')
                    ->first();

                $linkedPerson = $indexedImage?->person;
                if ($linkedPerson && (int) $linkedPerson->institution_id === (int) $institution->id) {
                    $personByFace = $linkedPerson;
                }
            }

            if ($externalImageIdRaw) {
                $documentNumber = pathinfo((string) $externalImageIdRaw, PATHINFO_FILENAME);
                if ($documentNumber === '') {
                    continue;
                }

                $person = People::where('institution_id', $institution->id)
                    ->where('document_number', $documentNumber)
                    ->first();

                if ($person) {
                    $personByExternal = $person;
                }
            }

            if ($personByFace && $personByExternal) {
                if ((int) $personByFace->id !== (int) $personByExternal->id) {
                    Log::warning('Conflicto de mapeo en match de Rekognition, se descarta coincidencia', [
                        'institution_id' => $institution->id,
                        'face_id' => $faceId,
                        'external_image_id' => $externalImageIdRaw,
                        'person_by_face_id' => $personByFace->id,
                        'person_by_external_id' => $personByExternal->id,
                        'similarity' => $match['similarity'] ?? null,
                    ]);
                    continue;
                }

                return [
                    'person' => $personByFace,
                    'match' => $match,
                    'resolved_by' => 'face_id+external_image_id',
                ];
            }

            if ($personByFace) {
                return [
                    'person' => $personByFace,
                    'match' => $match,
                    'resolved_by' => 'face_id',
                ];
            }

            if ($personByExternal) {
                return [
                    'person' => $personByExternal,
                    'match' => $match,
                    'resolved_by' => 'external_image_id',
                ];
            }
        }

        return null;
    }
}
