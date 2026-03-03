<?php

/*
|--------------------------------------------------------------------------
| API Routes - Face Recognition
|--------------------------------------------------------------------------
|
| Rutas para integración con AWS Rekognition
| Autenticación: Bearer Token (Laravel Sanctum)
| Base URL: /api/v1
|
*/

use App\Http\Controllers\Api\FaceRecognitionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // ===== COLECCIONES =====

    /**
     * Crear una nueva colección
     * POST /api/v1/collections
     *
     * Request:
     * {
     *   "collection_id": "employees",
     *   "description": "Colección de empleados"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "collection_arn": "arn:aws:rekognition:...",
     *   "status_code": 200,
     *   "message": "Colección 'employees' creada correctamente"
     * }
     */
    Route::post('collections', [FaceRecognitionController::class, 'createCollection']);

    /**
     * Listar todas las colecciones
     * GET /api/v1/collections?max_results=100&next_token=...
     *
     * Response:
     * {
     *   "success": true,
     *   "collections": [
     *     {
     *       "collection_id": "employees",
     *       "created_at": null,
     *       "face_count": null
     *     }
     *   ],
     *   "count": 1,
     *   "next_token": null,
     *   "message": "1 colecciones encontradas"
     * }
     */
    Route::get('collections', [FaceRecognitionController::class, 'listCollections']);

    /**
     * Obtener detalles de una colección
     * GET /api/v1/collections/{collectionId}
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "collection_arn": "arn:aws:rekognition:...",
     *   "face_count": 45,
     *   "face_model_version": "6.0",
     *   "creation_timestamp": 1704067200000
     * }
     */
    Route::get('collections/{collectionId}', [FaceRecognitionController::class, 'describeCollection']);

    /**
     * Eliminar una colección
     * DELETE /api/v1/collections/{collectionId}
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "status_code": 200,
     *   "message": "Colección 'employees' eliminada correctamente"
     * }
     */
    Route::delete('collections/{collectionId}', [FaceRecognitionController::class, 'deleteCollection']);

    // ===== ROSTROS =====

    /**
     * Indexar un rostro en una colección
     * POST /api/v1/collections/{collectionId}/faces
     *
     * Request:
     * {
     *   "external_image_id": "user_123",
     *   "image": "base64_encoded_image_or_s3_url",
     *   "is_base64": true
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "external_image_id": "user_123",
     *   "face_ids": ["face_id_1"],
     *   "face_count": 1,
     *   "message": "1 rostro(s) indexado(s) correctamente"
     * }
     */
    Route::post('collections/{collectionId}/faces', [FaceRecognitionController::class, 'indexFace']);

    /**
     * Buscar rostros similares en una colección
     * POST /api/v1/collections/{collectionId}/search
     *
     * Request:
     * {
     *   "image": "base64_encoded_image",
     *   "is_base64": true,
     *   "threshold": 80,
     *   "max_faces": 5
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "matches": [
     *     {
     *       "face_id": "face_id_1",
     *       "external_image_id": "user_123",
     *       "confidence": 97.45,
     *       "similarity": 97.45
     *     }
     *   ],
     *   "match_count": 1,
     *   "searched_face_confidence": 99.98,
     *   "message": "1 coincidencia(s) encontrada(s)"
     * }
     */
    Route::post('collections/{collectionId}/search', [FaceRecognitionController::class, 'searchFaces']);

    /**
     * Detectar rostros sin indexar
     * POST /api/v1/detect-faces
     *
     * Request:
     * {
     *   "image": "base64_encoded_image",
     *   "is_base64": true,
     *   "attributes": ["DEFAULT"]
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "faces": [
     *     {
     *       "bounding_box": {"Width": 0.5, "Height": 0.6, "Left": 0.2, "Top": 0.1},
     *       "confidence": 99.98,
     *       "landmarks": [...],
     *       "pose": {...},
     *       "quality": {...},
     *       "attributes": {
     *         "age_range": {"Low": 25, "High": 35},
     *         "smile": {"Value": true, "Confidence": 99.5},
     *         "gender": {"Value": "Male", "Confidence": 99.8}
     *       }
     *     }
     *   ],
     *   "face_count": 1,
     *   "message": "1 rostro(s) detectado(s)"
     * }
     */
    Route::post('detect-faces', [FaceRecognitionController::class, 'detectFaces']);

    /**
     * Eliminar rostros de una colección
     * DELETE /api/v1/collections/{collectionId}/faces
     *
     * Request:
     * {
     *   "face_ids": ["face_id_1", "face_id_2"]
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "collection_id": "employees",
     *   "deleted_faces": ["face_id_1"],
     *   "unsuccessful_face_deletions": [],
     *   "message": "1 rostro(s) eliminado(s)"
     * }
     */
    Route::delete('collections/{collectionId}/faces', [FaceRecognitionController::class, 'deleteFaces']);
});

