<?php

namespace App\Http\Controllers\Validate;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\People;
use App\Models\RekognitionIndexedImage;
use App\Models\ValidationLog;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ValidateController extends Controller
{
    protected RekognitionService $rekognition;

    public function __construct(RekognitionService $rekognition)
    {
        $this->rekognition = $rekognition;
    }

    public function index($uuid)
    {
        // Obtener institución por UUID
        $institution = Institution::where('uuid', $uuid)->firstOrFail();

        // Validar si la institución está activa
        if (!$institution->is_active) {
            return view('validate.inactive', [
                'institution' => $institution,
                'inactive_reason' => 'inactive',
            ]);
        }

        // Validar si superó el límite de validaciones contratadas
        if ($institution->isValidationQuotaExceeded()) {
            return view('validate.inactive', [
                'institution' => $institution,
                'inactive_reason' => 'quota_exceeded',
            ]);
        }

        return view('validate.index', [
            'institution' => $institution,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Analizar rostro de la foto capturada
     */
    public function analyzeFace(Request $request, $uuid): JsonResponse
    {
        try {
            // Validación de imagen
            if (!$request->has('image') || empty($request->input('image'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió imagen',
                ], 400);
            }

            $imageData = $request->input('image');

            // Obtener institución por UUID
            $institution = Institution::where('uuid', $uuid)->first();

            if (!$institution) {
                return response()->json([
                    'success' => false,
                    'message' => 'Institución no encontrada',
                ], 404);
            }

            if (!$institution->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'La institución está desactivada',
                    'type' => 'inactive',
                ], 403);
            }

            if ($institution->isValidationQuotaExceeded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La institución alcanzó el límite de validaciones contratadas',
                    'type' => 'quota_exceeded',
                    'data' => [
                        'validations_contracted' => $institution->validations_contracted,
                        'validations_used' => $institution->validations_used,
                        'validations_remaining' => $institution->validations_remaining,
                    ],
                ], 403);
            }

            // Validar colección Rekognition
            if (!$institution->rekognition_collection_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La institución no tiene una colección de Rekognition configurada',
                ], 400);
            }

            $collection = $institution->rekognitionCollection;
            if (!$collection || !$collection->collection_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Colección de Rekognition no encontrada',
                ], 400);
            }

            // Consume una validación de forma atómica para evitar sobrepasar el límite en concurrencia
            if (!$this->consumeValidationAttempt($institution)) {
                $institution->refresh();

                return response()->json([
                    'success' => false,
                    'message' => 'La institución alcanzó el límite de validaciones contratadas',
                    'type' => 'quota_exceeded',
                    'data' => [
                        'validations_contracted' => $institution->validations_contracted,
                        'validations_used' => $institution->validations_used,
                        'validations_remaining' => $institution->validations_remaining,
                    ],
                ], 403);
            }

            // Procesar imagen base64
            if (strpos($imageData, 'data:image') === 0) {
                $imageData = explode(',', $imageData)[1];
            }

            // Buscar rostro en la colección
            $searchResult = $this->rekognition->searchFacesByImage(
                collectionId: $collection->collection_id,
                imageData: $imageData,
                isBase64: true,
                faceMatchThreshold: 80,
                maxFaces: 5
            );

            if (!$searchResult['success']) {
                $responsePayload = [
                    'success' => false,
                    'message' => $searchResult['message'] ?? 'Error en el análisis de rostro',
                ];

                ValidationLog::create([
                    'institution_id' => $institution->id,
                    'document_number' => null,
                    'similarity' => null,
                    'matched' => false,
                    'validated_at' => now(),
                    'response' => $responsePayload,
                ]);

                return response()->json($responsePayload, 400);
            }

            // Verificar si hay coincidencias
            $matches = $searchResult['matches'] ?? [];

            if (empty($matches)) {
                $responsePayload = [
                    'success' => false,
                    'message' => 'No se encontró coincidencia con ningún registro',
                    'type' => 'no_match',
                ];

                ValidationLog::create([
                    'institution_id' => $institution->id,
                    'document_number' => null,
                    'similarity' => null,
                    'matched' => false,
                    'validated_at' => now(),
                    'response' => $responsePayload,
                ]);

                return response()->json($responsePayload);
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
                $externalImageId = $externalImageIdRaw ? pathinfo((string) $externalImageIdRaw, PATHINFO_FILENAME) : null;

                Log::warning('Validación sin mapeo de persona pese a coincidencias en Rekognition', [
                    'institution_id' => $institution->id,
                    'collection_id' => $collection->collection_id ?? null,
                    'matches_count' => count($matches),
                    'first_match' => $bestMatch,
                ]);

                $responsePayload = [
                    'success' => false,
                    'message' => 'Coincidencia sin mapeo de persona en base de datos',
                    'type' => 'no_match',
                    'data' => [
                        'document_number' => $externalImageId,
                        'similarity' => round((float) $similarity, 2),
                        'face_id' => $faceId,
                        'external_image_id' => $externalImageIdRaw,
                    ],
                ];

                ValidationLog::create([
                    'institution_id' => $institution->id,
                    'document_number' => $externalImageId,
                    'similarity' => $similarity,
                    'matched' => false,
                    'validated_at' => now(),
                    'response' => $responsePayload,
                ]);

                return response()->json($responsePayload);
            }

            // Procesar mejor coincidencia con persona resuelta
            $bestMatch = $resolved['match'];
            /** @var People $person */
            $person = $resolved['person'];
            $resolvedBy = $resolved['resolved_by'] ?? 'unknown';
            $similarity = $bestMatch['similarity'] ?? 0;

            // El external_image_id está directamente en el match, no en face
            $externalImageIdRaw = $bestMatch['external_image_id'] ?? null;
            $faceId = $bestMatch['face_id'] ?? null;

            Log::info('Validación resuelta con persona mapeada', [
                'institution_id' => $institution->id,
                'collection_id' => $collection->collection_id ?? null,
                'person_id' => $person->id,
                'document_number' => $person->document_number,
                'similarity' => $similarity,
                'face_id' => $faceId,
                'external_image_id' => $externalImageIdRaw,
                'resolved_by' => $resolvedBy,
            ]);

            // Preparar respuesta - Buscar foto
            $photoPath = $person->photo_path;
            $photoUrl = null;

            if ($photoPath) {
                // Construir URL de la foto
                // photo_path tiene formato: {filepath}/{document_number}.jpg
                $photoUrl = asset('storage/' . $photoPath);
            }

            $responsePayload = [
                'success' => true,
                'message' => 'Persona validada correctamente',
                'type' => 'match',
                'data' => [
                    'names' => $person->names,
                    'document_number' => $person->document_number,
                    'institution' => $institution->name,
                    'event' => $institution->event,
                    'similarity' => round($similarity, 2),
                    'photo_url' => $photoUrl,
                    'created_at' => $person->created_at->format('Y-m-d'),
                    'metadata' => $person->metadata,
                    'resolved_by' => $resolvedBy,
                ],
            ];

            // Registrar validación exitosa
            ValidationLog::create([
                'institution_id' => $institution->id,
                'document_number' => $person->document_number,
                'similarity' => $similarity,
                'matched' => true,
                'validated_at' => now(),
                'response' => $responsePayload,
            ]);

            return response()->json($responsePayload);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada',
            ], 404);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Descuenta una validación para la institución de forma atómica.
     * Si tiene límite, solo incrementa cuando validations_used < validations_contracted.
     */
    private function consumeValidationAttempt(Institution $institution): bool
    {
        if ($institution->validations_contracted === null) {
            Institution::whereKey($institution->id)->increment('validations_used');
            return true;
        }

        $updated = Institution::query()
            ->whereKey($institution->id)
            ->whereColumn('validations_used', '<', 'validations_contracted')
            ->increment('validations_used');

        return $updated > 0;
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
