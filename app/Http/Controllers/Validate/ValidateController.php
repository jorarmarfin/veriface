<?php

namespace App\Http\Controllers\Validate;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\People;
use App\Models\ValidationLog;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
                return response()->json([
                    'success' => false,
                    'message' => $searchResult['message'] ?? 'Error en el análisis de rostro',
                ], 400);
            }

            // Verificar si hay coincidencias
            $matches = $searchResult['matches'] ?? [];

            if (empty($matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró coincidencia con ningún registro',
                    'type' => 'no_match',
                ]);
            }

            // Procesar mejor coincidencia
            $bestMatch = $matches[0];
            $similarity = $bestMatch['similarity'] ?? 0;

            // El external_image_id está directamente en el match, no en face
            $externalImageIdRaw = $bestMatch['external_image_id'] ?? null;
            $faceId = $bestMatch['face_id'] ?? null;

            // Remover extensión .jpg del external_image_id para obtener document_number
            $externalImageId = pathinfo($externalImageIdRaw, PATHINFO_FILENAME);

            // Buscar persona en BD
            $person = People::where('institution_id', $institution->id)
                ->where('document_number', $externalImageId)
                ->first();

            if (!$person) {
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

            // Registrar validación exitosa
            ValidationLog::create([
                'institution_id' => $institution->id,
                'document_number' => $person->document_number,
                'similarity' => $similarity,
                'matched' => true,
                'validated_at' => now(),
            ]);

            // Preparar respuesta - Buscar foto
            $photoPath = $person->photo_path;
            $photoUrl = null;

            if ($photoPath) {
                // Intentar diferentes rutas posibles
                $possiblePaths = [
                    storage_path('app/public/' . $photoPath),
                    storage_path('app/' . $photoPath),
                    public_path($photoPath),
                ];

                $photoExists = false;
                $existingPath = null;

                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $photoExists = true;
                        $existingPath = $path;
                        break;
                    }
                }

                if ($photoExists) {
                    // Construir URL basada en donde se encontró el archivo
                    if (strpos($existingPath, storage_path('app/public')) !== false) {
                        $relativePath = str_replace(storage_path('app/public/'), '', $existingPath);
                        $photoUrl = asset('storage/' . $relativePath);
                    } elseif (strpos($existingPath, public_path()) !== false) {
                        $relativePath = str_replace(public_path() . '/', '', $existingPath);
                        $photoUrl = asset($relativePath);
                    }
                }
            }

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
}
