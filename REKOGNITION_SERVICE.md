# VeriFace - AWS Rekognition Service Documentation

## Descripción General

`RekognitionService` es una clase que encapsula toda la funcionalidad de AWS Rekognition para reconocimiento facial. Proporciona métodos para crear colecciones, indexar rostros, buscar similitudes y detectar rostros en imágenes.

## Configuración

### 1. Instalar dependencias

El proyecto ya tiene instalado `aws/aws-sdk-php-laravel`:

```bash
composer require aws/aws-sdk-php-laravel ^3.10
```

### 2. Configurar credenciales AWS

En el archivo `.env`:

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
```

### 3. Publicar configuración de AWS (si es necesario)

```bash
php artisan vendor:publish --provider="Aws\Laravel\AwsServiceProvider"
```

## Uso del Servicio

### Inyección de dependencias

```php
use App\Services\RekognitionService;

class MyController extends Controller
{
    public function __construct(private RekognitionService $rekognition)
    {
    }
    
    public function example()
    {
        // Usar el servicio
        $result = $this->rekognition->createCollection('my-collection');
    }
}
```

### Métodos disponibles

#### 1. Crear una colección

```php
$result = $this->rekognition->createCollection(
    collectionId: 'employees',
    description: 'Colección de empleados para asistencia'
);

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'collection_arn' => 'arn:aws:rekognition:us-east-1:...',
//     'status_code' => 200,
//     'message' => "Colección 'employees' creada correctamente"
// ]
```

#### 2. Listar colecciones

```php
$result = $this->rekognition->listCollections(
    maxResults: 100,
    nextToken: null
);

// Response:
// [
//     'success' => true,
//     'collections' => [
//         ['collection_id' => 'employees', 'created_at' => null, 'face_count' => null],
//         ['collection_id' => 'visitors', 'created_at' => null, 'face_count' => null]
//     ],
//     'count' => 2,
//     'next_token' => null,
//     'message' => '2 colecciones encontradas'
// ]
```

#### 3. Obtener detalles de una colección

```php
$result = $this->rekognition->describeCollection(collectionId: 'employees');

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'collection_arn' => 'arn:aws:rekognition:...',
//     'face_count' => 45,
//     'face_model_version' => '6.0',
//     'creation_timestamp' => 1704067200000
// ]
```

#### 4. Eliminar una colección

```php
$result = $this->rekognition->deleteCollection(collectionId: 'employees');

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'status_code' => 200,
//     'message' => "Colección 'employees' eliminada correctamente"
// ]
```

#### 5. Indexar un rostro

```php
$result = $this->rekognition->indexFace(
    collectionId: 'employees',
    externalImageId: 'user_123',
    imageData: base64_encode(file_get_contents('photo.jpg')),
    isBase64: true
);

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'external_image_id' => 'user_123',
//     'face_ids' => ['face_id_abc123'],
//     'face_count' => 1,
//     'face_details' => [...],
//     'unindexed_faces' => [],
//     'message' => '1 rostro(s) indexado(s) correctamente'
// ]
```

#### 6. Buscar rostros similares

```php
$result = $this->rekognition->searchFacesByImage(
    collectionId: 'employees',
    imageData: base64_encode(file_get_contents('query.jpg')),
    isBase64: true,
    faceMatchThreshold: 80,
    maxFaces: 5
);

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'matches' => [
//         [
//             'face_id' => 'face_id_abc123',
//             'external_image_id' => 'user_123',
//             'confidence' => 97.45,
//             'similarity' => 97.45
//         ]
//     ],
//     'match_count' => 1,
//     'face_model_version' => '6.0',
//     'searched_face_confidence' => 99.98,
//     'message' => '1 coincidencia(s) encontrada(s)'
// ]
```

#### 7. Detectar rostros (sin indexar)

```php
$result = $this->rekognition->detectFaces(
    imageData: base64_encode(file_get_contents('image.jpg')),
    isBase64: true,
    attributes: ['DEFAULT']  // o ['ALL'] para más atributos
);

// Response:
// [
//     'success' => true,
//     'faces' => [
//         [
//             'bounding_box' => ['Width' => 0.5, 'Height' => 0.6, 'Left' => 0.2, 'Top' => 0.1],
//             'confidence' => 99.98,
//             'landmarks' => [...],
//             'pose' => [...],
//             'quality' => [...],
//             'attributes' => [
//                 'age_range' => ['Low' => 25, 'High' => 35],
//                 'smile' => ['Value' => true, 'Confidence' => 99.5],
//                 'gender' => ['Value' => 'Male', 'Confidence' => 99.8],
//                 'eyeglasses' => ['Value' => false, 'Confidence' => 99.2],
//                 ...
//             ]
//         ]
//     ],
//     'face_count' => 1,
//     'image_width' => 1280,
//     'image_height' => 720,
//     'message' => '1 rostro(s) detectado(s)'
// ]
```

#### 8. Eliminar rostros

```php
$result = $this->rekognition->deleteFaces(
    collectionId: 'employees',
    faceIds: ['face_id_abc123', 'face_id_xyz789']
);

// Response:
// [
//     'success' => true,
//     'collection_id' => 'employees',
//     'deleted_faces' => ['face_id_abc123', 'face_id_xyz789'],
//     'unsuccessful_face_deletions' => [],
//     'message' => '2 rostro(s) eliminado(s)'
// ]
```

## Ejemplo de Caso de Uso: Sistema de Asistencia

```php
use App\Services\RekognitionService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private RekognitionService $rekognition)
    {
    }

    /**
     * Registrar asistencia por reconocimiento facial
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'photo' => 'required|image|max:5120' // máx 5MB
        ]);

        // Convertir imagen a base64
        $imageData = base64_encode(file_get_contents(
            $validated['photo']->getRealPath()
        ));

        // Buscar rostro en la colección
        $result = $this->rekognition->searchFacesByImage(
            collectionId: 'employees',
            imageData: $imageData,
            isBase64: true,
            faceMatchThreshold: 90  // Umbral alto para asistencia
        );

        if (!$result['success'] || empty($result['matches'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rostro no encontrado'
            ], 404);
        }

        // El primer resultado es la coincidencia más cercana
        $match = $result['matches'][0];
        $userId = $match['external_image_id'];
        $confidence = $match['similarity'];

        // Registrar asistencia
        Attendance::create([
            'user_id' => $userId,
            'timestamp' => now(),
            'confidence' => $confidence
        ]);

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'confidence' => $confidence,
            'message' => "Asistencia registrada para usuario {$userId}"
        ]);
    }

    /**
     * Registrar un nuevo empleado con foto
     */
    public function registerEmployee(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string|max:255',
            'photo' => 'required|image|max:5120'
        ]);

        // Convertir imagen a base64
        $imageData = base64_encode(file_get_contents(
            $validated['photo']->getRealPath()
        ));

        // Indexar rostro en la colección
        $result = $this->rekognition->indexFace(
            collectionId: 'employees',
            externalImageId: $validated['user_id'],
            imageData: $imageData,
            isBase64: true
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el rostro'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'user_id' => $validated['user_id'],
            'face_ids' => $result['face_ids'],
            'message' => 'Empleado registrado exitosamente'
        ]);
    }
}
```

## Manejo de Errores

Todos los métodos retornan un array con la siguiente estructura:

```php
[
    'success' => bool,          // true si la operación fue exitosa
    'message' => string,        // Mensaje descriptivo
    'error' => string,          // (Opcional) Código de error AWS
    ...other fields             // Campos específicos de cada operación
]
```

## Logging

El servicio registra todas las operaciones automáticamente:

```php
// Successful operations logged at INFO level
Log::info("AWS Rekognition: Colección 'employees' creada exitosamente", [...]);

// Errors logged at ERROR level
Log::error("Error creando colección en AWS Rekognition: ...", [...]);

// Debug operations logged at DEBUG level
Log::debug("AWS Rekognition: Rostros detectados", [...]);
```

Ver logs en: `storage/logs/laravel.log`

## Límites y Cuotas

- **MaxResults**: 1-100 (paginación)
- **FaceMatchThreshold**: 0-100 (porcentaje)
- **MaxFaces**: 1-4096 (rostros similares por búsqueda)
- **Face IDs por eliminación**: Sin límite teórico
- **Colecciones por cuenta**: Varía según el plan AWS

## Próximas mejoras posibles

1. Caché de colecciones
2. Batch operations para múltiples rostros
3. Soporte para comparación de dos rostros (CompareFaces)
4. Análisis de atributos faciales avanzado
5. Webhooks para eventos de reconocimiento

## Soporte

Para reportar problemas o solicitar mejoras, contactar al equipo de desarrollo.

