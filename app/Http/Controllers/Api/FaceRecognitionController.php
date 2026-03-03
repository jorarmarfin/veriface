<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceRecognitionController extends Controller
{
    protected RekognitionService $rekognitionService;

    public function __construct(RekognitionService $rekognitionService)
    {
        $this->rekognitionService = $rekognitionService;
    }

    /**
     * Crear una nueva colección para almacenar rostros
     * POST /api/collections
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCollection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'collection_id' => 'required|string|max:255|unique:collections',
                'description' => 'nullable|string|max:500'
            ]);

            $result = $this->rekognitionService->createCollection(
                $validated['collection_id'],
                $validated['description'] ?? ''
            );

            return response()->json($result, $result['success'] ? 201 : 400);
        } catch (\Exception $e) {
            Log::error('Error creating collection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTrace()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la colección',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las colecciones disponibles
     * GET /api/collections
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listCollections(Request $request): JsonResponse
    {
        try {
            $maxResults = $request->query('max_results', 100);
            $nextToken = $request->query('next_token');

            $result = $this->rekognitionService->listCollections(
                min((int)$maxResults, 100),
                $nextToken
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Error listing collections', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al listar colecciones',
                'collections' => []
            ], 500);
        }
    }

    /**
     * Obtener detalles de una colección
     * GET /api/collections/{collectionId}
     *
     * @param string $collectionId
     * @return JsonResponse
     */
    public function describeCollection(string $collectionId): JsonResponse
    {
        try {
            $result = $this->rekognitionService->describeCollection($collectionId);

            return response()->json($result, $result['success'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('Error describing collection', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles de la colección'
            ], 500);
        }
    }

    /**
     * Eliminar una colección
     * DELETE /api/collections/{collectionId}
     *
     * @param string $collectionId
     * @return JsonResponse
     */
    public function deleteCollection(string $collectionId): JsonResponse
    {
        try {
            $result = $this->rekognitionService->deleteCollection($collectionId);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Error deleting collection', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la colección'
            ], 500);
        }
    }

    /**
     * Indexar un rostro en una colección
     * POST /api/collections/{collectionId}/faces
     *
     * @param Request $request
     * @param string $collectionId
     * @return JsonResponse
     */
    public function indexFace(Request $request, string $collectionId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'external_image_id' => 'required|string|max:255',
                'image' => 'required|string', // base64 o URL S3
                'is_base64' => 'boolean'
            ]);

            $result = $this->rekognitionService->indexFace(
                $collectionId,
                $validated['external_image_id'],
                $validated['image'],
                $validated['is_base64'] ?? true
            );

            return response()->json($result, $result['success'] ? 201 : 400);
        } catch (\Exception $e) {
            Log::error('Error indexing face', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al indexar el rostro'
            ], 500);
        }
    }

    /**
     * Buscar rostros similares en una colección
     * POST /api/collections/{collectionId}/search
     *
     * @param Request $request
     * @param string $collectionId
     * @return JsonResponse
     */
    public function searchFaces(Request $request, string $collectionId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'image' => 'required|string', // base64 o URL S3
                'is_base64' => 'boolean',
                'threshold' => 'nullable|numeric|min:0|max:100',
                'max_faces' => 'nullable|integer|min:1|max:4096'
            ]);

            $result = $this->rekognitionService->searchFacesByImage(
                $collectionId,
                $validated['image'],
                $validated['is_base64'] ?? true,
                $validated['threshold'] ?? 80,
                $validated['max_faces'] ?? 1
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Error searching faces', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar rostros',
                'matches' => []
            ], 500);
        }
    }

    /**
     * Detectar rostros en una imagen sin indexar
     * POST /api/detect-faces
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detectFaces(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'image' => 'required|string', // base64 o URL S3
                'is_base64' => 'boolean',
                'attributes' => 'nullable|array'
            ]);

            $result = $this->rekognitionService->detectFaces(
                $validated['image'],
                $validated['is_base64'] ?? true,
                $validated['attributes'] ?? ['DEFAULT']
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Error detecting faces', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al detectar rostros',
                'faces' => []
            ], 500);
        }
    }

    /**
     * Eliminar rostros de una colección
     * DELETE /api/collections/{collectionId}/faces
     *
     * @param Request $request
     * @param string $collectionId
     * @return JsonResponse
     */
    public function deleteFaces(Request $request, string $collectionId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'face_ids' => 'required|array|min:1',
                'face_ids.*' => 'string'
            ]);

            $result = $this->rekognitionService->deleteFaces(
                $collectionId,
                $validated['face_ids']
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Error deleting faces', [
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar rostros'
            ], 500);
        }
    }
}

