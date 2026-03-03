<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class RekognitionService
{
    protected RekognitionClient $client;

    /**
     * Constructor
     * Inicializa el cliente de AWS Rekognition usando las credenciales configuradas
     */
    public function __construct()
    {
        $this->client = app('aws')->createClient('rekognition', [
            'version' => 'latest',
            'region'  => config('aws.region', 'us-east-1')
        ]);
    }

    /**
     * Crear una nueva colección para almacenar rostros
     *
     * @param string $collectionId Identificador único de la colección
     * @param string $description Descripción opcional de la colección
     * @return array Resultado con información de la colección creada
     * @throws AwsException
     */
    public function createCollection(string $collectionId, string $description = ''): array
    {
        try {
            $params = [
                'CollectionId' => $collectionId,
                'Tags' => [
                    'Application' => 'VeriFace',
                    'Environment' => app()->environment(),
                    'CreatedAt' => now()->toIso8601String(),
                    'Description' => $description
                ]
            ];

            $response = $this->client->createCollection($params);

            Log::info("AWS Rekognition: Colección '{$collectionId}' creada exitosamente", [
                'collection_arn' => $response['CollectionArn'] ?? null,
                'status_code' => $response['@metadata']['statusCode'] ?? null
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'collection_arn' => $response['CollectionArn'] ?? null,
                'status_code' => $response['@metadata']['statusCode'] ?? 200,
                'message' => "Colección '{$collectionId}' creada correctamente"
            ];
        } catch (AwsException $e) {
            Log::error("Error creando colección en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar todas las colecciones disponibles
     *
     * @param int $maxResults Máximo de resultados a retornar (1-100)
     * @param string|null $nextToken Token para paginación
     * @return array Lista de colecciones con metadata
     * @throws AwsException
     */
    public function listCollections(int $maxResults = 100, ?string $nextToken = null): array
    {
        try {
            $params = [
                'MaxResults' => min($maxResults, 100)
            ];

            if ($nextToken) {
                $params['NextToken'] = $nextToken;
            }

            $response = $this->client->listCollections($params);

            $collections = array_map(fn($collectionId) => [
                'collection_id' => $collectionId,
                'created_at' => null, // AWS no retorna fecha de creación en listCollections
                'face_count' => null  // Requerir describir para obtener esta info
            ], $response['CollectionIds'] ?? []);

            Log::debug("AWS Rekognition: Colecciones listadas", [
                'count' => count($collections),
                'has_next_token' => !empty($response['NextToken'])
            ]);

            return [
                'success' => true,
                'collections' => $collections,
                'count' => count($collections),
                'next_token' => $response['NextToken'] ?? null,
                'message' => count($collections) . ' colecciones encontradas'
            ];
        } catch (AwsException $e) {
            Log::error("Error listando colecciones en AWS Rekognition: {$e->getMessage()}", [
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage(),
                'collections' => []
            ];
        }
    }

    /**
     * Obtener información detallada de una colección
     *
     * @param string $collectionId ID de la colección
     * @return array Información detallada de la colección
     * @throws AwsException
     */
    public function describeCollection(string $collectionId): array
    {
        try {
            $response = $this->client->describeCollection([
                'CollectionId' => $collectionId
            ]);

            Log::debug("AWS Rekognition: Colección '{$collectionId}' descrita", [
                'face_count' => $response['FaceCount'] ?? 0,
                'face_model_version' => $response['FaceModelVersion'] ?? null
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'collection_arn' => $response['CollectionArn'] ?? null,
                'face_count' => $response['FaceCount'] ?? 0,
                'face_model_version' => $response['FaceModelVersion'] ?? null,
                'creation_timestamp' => $response['CreationTimestamp'] ?? null
            ];
        } catch (AwsException $e) {
            Log::error("Error describiendo colección en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar una colección y todos sus rostros indexados
     *
     * @param string $collectionId ID de la colección a eliminar
     * @return array Resultado de la eliminación
     * @throws AwsException
     */
    public function deleteCollection(string $collectionId): array
    {
        try {
            $response = $this->client->deleteCollection([
                'CollectionId' => $collectionId
            ]);

            Log::info("AWS Rekognition: Colección '{$collectionId}' eliminada exitosamente", [
                'status_code' => $response['@metadata']['statusCode'] ?? null
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'status_code' => $response['@metadata']['statusCode'] ?? 200,
                'message' => "Colección '{$collectionId}' eliminada correctamente"
            ];
        } catch (AwsException $e) {
            Log::error("Error eliminando colección en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Indexar un rostro en una colección desde una imagen (base64 o URL)
     *
     * @param string $collectionId ID de la colección
     * @param string $externalImageId ID externo del usuario/registro
     * @param string $imageData Datos de la imagen (base64 o URL S3)
     * @param bool $isBase64 Si es true, $imageData es base64; si no, es URL S3
     * @param array $userAttributes Atributos adicionales del usuario
     * @return array Resultado del indexado
     */
    public function indexFace(
        string $collectionId,
        string $externalImageId,
        string $imageData,
        bool $isBase64 = true,
        array $userAttributes = []
    ): array {
        try {
            $params = [
                'CollectionId' => $collectionId,
                'ExternalImageId' => $externalImageId,
                'DetectionAttributes' => ['DEFAULT']
            ];

            // Preparar imagen según el formato
            if ($isBase64) {
                $params['Image'] = [
                    'Bytes' => base64_decode($imageData)
                ];
            } else {
                // S3 URL
                $params['Image'] = [
                    'S3Object' => [
                        'Bucket' => config('filesystems.disks.s3.bucket'),
                        'Name' => $imageData
                    ]
                ];
            }

            $response = $this->client->indexFaces($params);

            $faceIds = array_map(
                fn($face) => $face['Face']['FaceId'] ?? null,
                $response['FaceRecords'] ?? []
            );

            Log::info("AWS Rekognition: Rostro indexado en colección '{$collectionId}'", [
                'external_image_id' => $externalImageId,
                'face_count' => count($faceIds),
                'face_ids' => $faceIds
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'external_image_id' => $externalImageId,
                'face_ids' => $faceIds,
                'face_count' => count($faceIds),
                'face_details' => $response['FaceRecords'] ?? [],
                'unindexed_faces' => $response['UnindexedFaces'] ?? [],
                'message' => count($faceIds) . ' rostro(s) indexado(s) correctamente'
            ];
        } catch (AwsException $e) {
            Log::error("Error indexando rostro en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'external_image_id' => $externalImageId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar rostros similares en una colección
     *
     * @param string $collectionId ID de la colección
     * @param string $imageData Datos de la imagen a buscar (base64 o URL S3)
     * @param bool $isBase64 Si es true, $imageData es base64
     * @param float $faceMatchThreshold Umbral de similitud (0-100), por defecto 80%
     * @param int $maxFaces Máximo de rostros similares a retornar (1-4096)
     * @return array Rostros similares encontrados
     */
    public function searchFacesByImage(
        string $collectionId,
        string $imageData,
        bool $isBase64 = true,
        float $faceMatchThreshold = 80,
        int $maxFaces = 1
    ): array {
        try {
            $params = [
                'CollectionId' => $collectionId,
                'FaceMatchThreshold' => min(max($faceMatchThreshold, 0), 100),
                'MaxFaces' => min(max($maxFaces, 1), 4096)
            ];

            // Preparar imagen
            if ($isBase64) {
                $params['Image'] = [
                    'Bytes' => base64_decode($imageData)
                ];
            } else {
                $params['Image'] = [
                    'S3Object' => [
                        'Bucket' => config('filesystems.disks.s3.bucket'),
                        'Name' => $imageData
                    ]
                ];
            }

            $response = $this->client->searchFacesByImage($params);

            $matches = array_map(function($faceMatch) {
                return [
                    'face_id' => $faceMatch['Face']['FaceId'] ?? null,
                    'external_image_id' => $faceMatch['Face']['ExternalImageId'] ?? null,
                    'confidence' => $faceMatch['Similarity'] ?? 0,
                    'similarity' => $faceMatch['Similarity'] ?? 0
                ];
            }, $response['FaceMatches'] ?? []);

            Log::info("AWS Rekognition: Búsqueda de rostros en colección '{$collectionId}'", [
                'matches_found' => count($matches),
                'threshold' => $faceMatchThreshold
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'matches' => $matches,
                'match_count' => count($matches),
                'face_model_version' => $response['FaceModelVersion'] ?? null,
                'searched_face_confidence' => $response['SearchedFaceConfidence'] ?? null,
                'message' => count($matches) . ' coincidencia(s) encontrada(s)'
            ];
        } catch (AwsException $e) {
            Log::error("Error buscando rostros en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage(),
                'matches' => []
            ];
        }
    }

    /**
     * Detectar rostros en una imagen sin indexar
     *
     * @param string $imageData Datos de la imagen (base64 o URL S3)
     * @param bool $isBase64 Si es true, $imageData es base64
     * @param array $attributes Atributos a detectar (DEFAULT, ALL)
     * @return array Rostros detectados con sus coordenadas y atributos
     */
    public function detectFaces(
        string $imageData,
        bool $isBase64 = true,
        array $attributes = ['DEFAULT']
    ): array {
        try {
            $params = [
                'Image' => $isBase64
                    ? ['Bytes' => base64_decode($imageData)]
                    : [
                        'S3Object' => [
                            'Bucket' => config('filesystems.disks.s3.bucket'),
                            'Name' => $imageData
                        ]
                    ],
                'Attributes' => $attributes
            ];

            $response = $this->client->detectFaces($params);

            $faces = array_map(function($face) {
                return [
                    'bounding_box' => $face['BoundingBox'] ?? null,
                    'confidence' => $face['Confidence'] ?? 0,
                    'landmarks' => $face['Landmarks'] ?? [],
                    'pose' => $face['Pose'] ?? null,
                    'quality' => $face['ImageQuality'] ?? null,
                    'attributes' => array_filter([
                        'age_range' => $face['AgeRange'] ?? null,
                        'smile' => $face['Smile'] ?? null,
                        'eyeglasses' => $face['Eyeglasses'] ?? null,
                        'sunglasses' => $face['Sunglasses'] ?? null,
                        'gender' => $face['Gender'] ?? null,
                        'beard' => $face['Beard'] ?? null,
                        'mustache' => $face['Mustache'] ?? null,
                        'eyes_open' => $face['EyesOpen'] ?? null,
                        'mouth_open' => $face['MouthOpen'] ?? null,
                        'emotions' => $face['Emotions'] ?? null
                    ])
                ];
            }, $response['FaceDetails'] ?? []);

            Log::debug("AWS Rekognition: Rostros detectados", [
                'face_count' => count($faces)
            ]);

            return [
                'success' => true,
                'faces' => $faces,
                'face_count' => count($faces),
                'image_width' => $response['ImageWidth'] ?? null,
                'image_height' => $response['ImageHeight'] ?? null,
                'orientation' => $response['OrientationCorrection'] ?? null,
                'message' => count($faces) . ' rostro(s) detectado(s)'
            ];
        } catch (AwsException $e) {
            Log::error("Error detectando rostros en AWS Rekognition: {$e->getMessage()}", [
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage(),
                'faces' => []
            ];
        }
    }

    /**
     * Eliminar rostros de una colección por Face ID
     *
     * @param string $collectionId ID de la colección
     * @param array $faceIds Array de Face IDs a eliminar
     * @return array Resultado de la eliminación
     */
    public function deleteFaces(string $collectionId, array $faceIds): array
    {
        try {
            if (empty($faceIds)) {
                return [
                    'success' => false,
                    'message' => 'No face IDs provided'
                ];
            }

            $response = $this->client->deleteFaces([
                'CollectionId' => $collectionId,
                'FaceIds' => $faceIds
            ]);

            Log::info("AWS Rekognition: Rostros eliminados de colección '{$collectionId}'", [
                'deleted_count' => count($response['DeletedFaces'] ?? [])
            ]);

            return [
                'success' => true,
                'collection_id' => $collectionId,
                'deleted_faces' => $response['DeletedFaces'] ?? [],
                'unsuccessful_face_deletions' => $response['UnsuccessfulFaceDeletions'] ?? [],
                'message' => count($response['DeletedFaces'] ?? []) . ' rostro(s) eliminado(s)'
            ];
        } catch (AwsException $e) {
            Log::error("Error eliminando rostros en AWS Rekognition: {$e->getMessage()}", [
                'collection_id' => $collectionId,
                'error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getAwsErrorCode(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el cliente de Rekognition
     *
     * @return RekognitionClient
     */
    public function getClient(): RekognitionClient
    {
        return $this->client;
    }
}
