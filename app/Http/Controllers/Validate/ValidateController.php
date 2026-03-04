<?php

namespace App\Http\Controllers\Validate;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\People;
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
        Log::info('📱 Validación Biométrica - Acceso a página', [
            'uuid' => $uuid,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Obtener institución por UUID
        $institution = Institution::where('uuid', $uuid)->firstOrFail();

        Log::info('✅ Institución encontrada', [
            'institution_id' => $institution->id,
            'institution_name' => $institution->name,
            'rekognition_collection_id' => $institution->rekognition_collection_id,
        ]);

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
        $requestId = uniqid('validate_');
        Log::info("🚀 INICIO - Validación Biométrica [{$requestId}]", [
            'uuid' => $uuid,
            'timestamp' => now(),
        ]);

        try {
            // ============================================================
            // 1. VALIDACIÓN DE IMAGEN
            // ============================================================
            Log::info("📥 Paso 1: Validando imagen [{$requestId}]");

            if (!$request->has('image') || empty($request->input('image'))) {
                Log::warning("❌ [ERROR 1.1] No se recibió imagen [{$requestId}]");
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió imagen',
                ], 400);
            }

            $imageData = $request->input('image');
            $imageSizeBytes = strlen($imageData);
            Log::info("✅ Imagen recibida [{$requestId}]", [
                'size_bytes' => $imageSizeBytes,
                'size_mb' => round($imageSizeBytes / 1024 / 1024, 2),
                'has_data_prefix' => strpos($imageData, 'data:image') === 0 ? 'yes' : 'no',
            ]);

            // ============================================================
            // 2. OBTENER INSTITUCIÓN
            // ============================================================
            Log::info("🔍 Paso 2: Buscando institución por UUID [{$requestId}]", [
                'uuid' => $uuid,
            ]);

            $institution = Institution::where('uuid', $uuid)->first();

            if (!$institution) {
                Log::error("❌ [ERROR 2.1] Institución no encontrada [{$requestId}]", [
                    'uuid' => $uuid,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Institución no encontrada',
                ], 404);
            }

            Log::info("✅ Institución encontrada [{$requestId}]", [
                'institution_id' => $institution->id,
                'institution_name' => $institution->name,
                'institution_filepath' => $institution->filepath,
                'rekognition_collection_id' => $institution->rekognition_collection_id,
            ]);

            // ============================================================
            // 3. VALIDAR COLECCIÓN REKOGNITION
            // ============================================================
            Log::info("🔍 Paso 3: Validando colección Rekognition [{$requestId}]");

            if (!$institution->rekognition_collection_id) {
                Log::error("❌ [ERROR 3.1] La institución no tiene colección configurada [{$requestId}]", [
                    'institution_id' => $institution->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La institución no tiene una colección de Rekognition configurada',
                ], 400);
            }

            $collection = $institution->rekognitionCollection;
            if (!$collection || !$collection->collection_id) {
                Log::error("❌ [ERROR 3.2] Colección no encontrada o sin ID [{$requestId}]", [
                    'institution_id' => $institution->id,
                    'collection_id' => $institution->rekognition_collection_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Colección de Rekognition no encontrada',
                ], 400);
            }

            Log::info("✅ Colección validada [{$requestId}]", [
                'collection_id' => $collection->id,
                'collection_name' => $collection->name,
                'collection_aws_id' => $collection->collection_id,
                'is_active' => $collection->is_active,
            ]);

            // ============================================================
            // 4. PROCESAR IMAGEN BASE64
            // ============================================================
            Log::info("⚙️ Paso 4: Procesando imagen base64 [{$requestId}]");

            if (strpos($imageData, 'data:image') === 0) {
                $imageData = explode(',', $imageData)[1];
                Log::info("✅ Prefijo data:image removido [{$requestId}]");
            }

            $decodedImage = base64_decode($imageData);
            Log::info("✅ Imagen decodificada [{$requestId}]", [
                'decoded_size_bytes' => strlen($decodedImage),
                'decoded_size_mb' => round(strlen($decodedImage) / 1024 / 1024, 2),
            ]);

            // ============================================================
            // 5. LLAMAR A REKOGNITION
            // ============================================================
            Log::info("🔍 Paso 5: Enviando imagen a AWS Rekognition [{$requestId}]", [
                'service' => 'searchFacesByImage',
                'collection_id' => $collection->collection_id,
                'threshold' => 80,
                'max_faces' => 5,
            ]);

            $searchResult = $this->rekognition->searchFacesByImage(
                collectionId: $collection->collection_id,
                imageData: $imageData,
                isBase64: true,
                faceMatchThreshold: 80,
                maxFaces: 5
            );

            Log::info("📊 Respuesta de Rekognition recibida [{$requestId}]", [
                'success' => $searchResult['success'] ?? false,
                'matches_count' => count($searchResult['matches'] ?? []),
                'response_keys' => array_keys($searchResult),
            ]);

            // ============================================================
            // 6. VALIDAR RESPUESTA DE REKOGNITION
            // ============================================================
            Log::info("⚙️ Paso 6: Validando respuesta de Rekognition [{$requestId}]");

            if (!$searchResult['success']) {
                Log::error("❌ [ERROR 6.1] Error en búsqueda de rostro [{$requestId}]", [
                    'message' => $searchResult['message'] ?? 'Error desconocido',
                    'full_response' => $searchResult,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $searchResult['message'] ?? 'Error en el análisis de rostro',
                ], 400);
            }

            Log::info("✅ Respuesta de Rekognition válida [{$requestId}]");

            // ============================================================
            // 7. VERIFICAR COINCIDENCIAS
            // ============================================================
            Log::info("🔍 Paso 7: Buscando coincidencias en resultados [{$requestId}]");

            $matches = $searchResult['matches'] ?? [];

            if (empty($matches)) {
                Log::warning("⚠️ Sin coincidencias encontradas [{$requestId}]", [
                    'collection_id' => $collection->collection_id,
                    'threshold' => 80,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró coincidencia con ningún registro',
                    'type' => 'no_match',
                ]);
            }

            Log::info("✅ Coincidencias encontradas [{$requestId}]", [
                'total_matches' => count($matches),
                'best_match_similarity' => $matches[0]['similarity'] ?? null,
            ]);

            // ============================================================
            // 8. PROCESAR MEJOR COINCIDENCIA
            // ============================================================
            Log::info("🎯 Paso 8: Procesando mejor coincidencia [{$requestId}]");

            $bestMatch = $matches[0];
            $similarity = $bestMatch['similarity'] ?? 0;

            // El external_image_id está directamente en el match, no en face
            $externalImageIdRaw = $bestMatch['external_image_id'] ?? null;
            $faceId = $bestMatch['face_id'] ?? null;

            // Remover extensión .jpg del external_image_id para obtener document_number
            $externalImageId = pathinfo($externalImageIdRaw, PATHINFO_FILENAME);

            Log::info("📊 Detalles de mejor coincidencia [{$requestId}]", [
                'similarity' => $similarity,
                'face_id' => $faceId,
                'external_image_id_raw' => $externalImageIdRaw,
                'external_image_id_cleaned' => $externalImageId,
                'confidence' => $bestMatch['confidence'] ?? null,
            ]);

            // ============================================================
            // 9. BUSCAR PERSONA EN BD
            // ============================================================
            Log::info("🔍 Paso 9: Buscando persona en BD [{$requestId}]", [
                'institution_id' => $institution->id,
                'document_number' => $externalImageId,
            ]);

            $person = People::where('institution_id', $institution->id)
                ->where('document_number', $externalImageId)
                ->first();

            if (!$person) {
                Log::warning("⚠️ Persona no encontrada en BD [{$requestId}]", [
                    'institution_id' => $institution->id,
                    'document_number' => $externalImageId,
                    'similarity' => $similarity,
                ]);

                // Crear registro de validación fallida
                ValidationLog::create([
                    'institution_id' => $institution->id,
                    'document_number' => $externalImageId,
                    'similarity' => $similarity,
                    'matched' => false,
                    'validated_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Persona no encontrada en la base de datos',
                    'type' => 'no_match',
                ]);
            }

            Log::info("✅ Persona encontrada en BD [{$requestId}]", [
                'person_id' => $person->id,
                'person_name' => $person->names,
                'person_document' => $person->document_number,
                'person_photo_path' => $person->photo_path,
            ]);

            // ============================================================
            // 10. REGISTRAR VALIDACIÓN
            // ============================================================
            Log::info("💾 Paso 10: Registrando validación en BD [{$requestId}]");

            $validationLog = ValidationLog::create([
                'institution_id' => $institution->id,
                'document_number' => $person->document_number,
                'similarity' => $similarity,
                'matched' => true,
                'validated_at' => now(),
            ]);

            Log::info("✅ Validación registrada [{$requestId}]", [
                'validation_log_id' => $validationLog->id,
                'timestamp' => $validationLog->created_at,
            ]);

            // ============================================================
            // 11. PREPARAR RESPUESTA
            // ============================================================
            Log::info("📦 Paso 11: Preparando respuesta [{$requestId}]");

            $photoPath = $person->photo_path;
            $photoUrl = null;

            if ($photoPath) {
                $fullPath = storage_path('app/public/' . $photoPath);
                $photoExists = file_exists($fullPath);

                Log::info("📸 Verificando foto de persona [{$requestId}]", [
                    'photo_path' => $photoPath,
                    'full_path' => $fullPath,
                    'exists' => $photoExists,
                ]);

                if ($photoExists) {
                    $photoUrl = asset('storage/' . $photoPath);
                    Log::info("✅ URL de foto generada [{$requestId}]", [
                        'photo_url' => $photoUrl,
                    ]);
                } else {
                    Log::warning("⚠️ Archivo de foto no existe [{$requestId}]", [
                        'expected_path' => $fullPath,
                    ]);
                }
            }

            // ============================================================
            // 12. RESPUESTA EXITOSA
            // ============================================================
            Log::info("✅ VALIDACIÓN EXITOSA [{$requestId}]", [
                'person_id' => $person->id,
                'similarity' => $similarity,
                'photo_url_available' => !is_null($photoUrl),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Persona validada correctamente',
                'type' => 'match',
                'data' => [
                    'names' => $person->names,
                    'document_number' => $person->document_number,
                    'institution' => $institution->name,
                    'similarity' => round($similarity, 2),
                    'photo_url' => $photoUrl,
                    'created_at' => $person->created_at->format('Y-m-d'),
                    'metadata' => $person->metadata,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("❌ [EXCEPTION] Entidad no encontrada [{$requestId}]", [
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada',
            ], 404);
        } catch (\Exception $e) {
            Log::error("❌ [EXCEPTION] Error general [{$requestId}]", [
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
