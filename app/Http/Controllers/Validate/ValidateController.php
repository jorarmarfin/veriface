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
            // Validar que hay imagen
            if (!$request->has('image') || empty($request->input('image'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió imagen',
                ], 400);
            }

            // Obtener institución por UUID
            $institution = Institution::where('uuid', $uuid)->firstOrFail();

            // Validar que la institución tiene colección de rekognition
            if (!$institution->rekognition_collection_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La institución no tiene una colección de Rekognition configurada',
                ], 400);
            }

            // Obtener la colección
            $collection = $institution->rekognitionCollection;
            if (!$collection || !$collection->collection_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Colección de Rekognition no encontrada',
                ], 400);
            }

            // Obtener la imagen en base64
            $imageData = $request->input('image');

            // Remover el prefijo data:image/jpeg;base64, si existe
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
            if (empty($searchResult['matches'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró coincidencia con ningún registro',
                    'type' => 'no_match',
                ]);
            }

            // Obtener la mejor coincidencia
            $bestMatch = $searchResult['matches'][0];
            $similarity = $bestMatch['similarity'] ?? 0;
            $externalImageId = $bestMatch['face']['external_image_id'] ?? null;

            // Buscar persona por document_number (externalImageId)
            $person = People::where('institution_id', $institution->id)
                ->where('document_number', $externalImageId)
                ->first();

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'Persona no encontrada en la base de datos',
                    'type' => 'no_match',
                ]);
            }

            // Registrar validación
            ValidationLog::create([
                'institution_id' => $institution->id,
                'document_number' => $person->document_number,
                'similarity' => $similarity,
                'matched' => true,
            ]);

            // Preparar datos de respuesta
            $photoPath = $person->photo_path;
            $photoUrl = null;

            // Construir URL de foto si existe
            if ($photoPath && file_exists(storage_path('app/public/' . $photoPath))) {
                $photoUrl = asset('storage/' . $photoPath);
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
