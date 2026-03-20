# Contexto del sistema VeriFace

Última actualización de este contexto: 19 de marzo de 2026.

## 1) Resumen general

VeriFace es una aplicación de validación biométrica facial construida con:

- Laravel 12 (PHP 8.2)
- Filament 5 (panel administrativo)
- AWS Rekognition (colecciones, indexación y búsqueda facial)
- MySQL (modelo relacional principal)
- Maatwebsite Excel (importación masiva de postulantes)

Objetivo funcional:

- Administrar instituciones, personas/postulantes y colecciones de Rekognition.
- Indexar rostros en AWS Rekognition.
- Validar rostros en un flujo público de cámara y registrar resultados en logs de validación.

## 2) Estructura funcional principal

### 2.1 Validación pública (frontend + backend)

Rutas web:

- `GET /validate/{uuid}`: muestra interfaz de validación por institución.
- `POST /validate/{uuid}/analyze`: captura la imagen y ejecuta búsqueda facial.

Flujo:

- La vista pública usa cámara del navegador (`getUserMedia`), captura imagen en base64 y hace POST al endpoint de análisis.
- El controlador de validación:
- Verifica institución por `uuid`.
- Verifica que esté activa.
- Verifica que tenga colección Rekognition asociada.
- Busca coincidencias en Rekognition (`searchFacesByImage`).
- Mapea `external_image_id` a `document_number` (quitando extensión de archivo).
- Busca la persona en BD (`people`).
- Registra resultado en `validation_logs` (éxito o no match).
- Devuelve datos de persona y similitud para mostrar en UI.

Archivos clave:

- `routes/web.php`
- `app/Http/Controllers/Validate/ValidateController.php`
- `resources/views/validate/index.blade.php`
- `resources/views/validate/inactive.blade.php`

### 2.2 Panel Filament (administración)

Panel:

- Provider: `app/Providers/Filament/AdminPanelProvider.php`
- URL panel: `/admin`

Módulos funcionales principales:

- Gestión de instituciones.
- Gestión de personas (postulantes).
- Gestión de logs de validación.
- Gestión de colecciones de Rekognition.
- Gestión de imágenes indexadas.

Pantalla central de Rekognition:

- `app/Filament/Pages/CollectionsPage.php`
- Funciones:
- Sincronizar colecciones desde AWS.
- Sincronizar imágenes indexadas desde AWS.
- Crear/eliminar colecciones.
- Buscar rostro en todas las colecciones activas.
- Indexar fotos desde carpeta local `storage/app/public/fotos`.

Gestión por institución:

- `app/Filament/Resources/Institutions/InstitutionResource.php`
- Permite:
- Asignar colección Rekognition a institución.
- Acceder al enlace de validación pública por UUID.
- Indexar fotos de la carpeta de la institución.

Importación masiva de personas:

- Relación de personas en institución:
- `app/Filament/Resources/Institutions/Pages/PeopleRelationManager.php`
- Importador:
- `app/Imports/PeopleImport.php`
- Regla de `photo_path` generada:
- `{filepath}/{document_number}.jpg`

### 2.3 Servicio AWS Rekognition (núcleo de integración)

Servicio central:

- `app/Services/RekognitionService.php`

Métodos principales:

- `createCollection`
- `listCollections`
- `describeCollection`
- `deleteCollection`
- `indexFace`
- `searchFacesByImage`
- `detectFaces`
- `deleteFaces`
- `listFaces`

## 3) Modelo de datos (resumen)

Tablas principales:

- `institutions`
- `people`
- `validation_logs`
- `rekognition_collections`
- `rekognition_indexed_images`

Relaciones importantes:

- `Institution` tiene muchas `People`.
- `Institution` tiene muchos `ValidationLog`.
- `Institution` pertenece a `RekognitionCollection` (FK `rekognition_collection_id`).
- `RekognitionCollection` tiene muchas `RekognitionIndexedImage`.
- `RekognitionIndexedImage` pertenece a `People` (nullable).

Notas:

- `Institution` genera automáticamente `uuid` y `slug`.
- `Institution` crea automáticamente carpeta física en `storage/app/public/{filepath}` al crear/actualizar.

## 4) Configuración e infraestructura

Dependencias relevantes (`composer.json`):

- `aws/aws-sdk-php-laravel`
- `filament/filament`
- `maatwebsite/excel`
- `rap2hpoutre/laravel-log-viewer`

Configuración AWS:

- `config/aws.php`
- Variables esperadas:
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`

Filesystem:

- `local` => `storage/app/private`
- `public` => `storage/app/public` (con symlink a `public/storage`)

## 5) Comandos Artisan personalizados

Archivos en `app/Console/Commands`:

- `SyncIndexedImages` (`rekognition:sync-indexed-images`)
- `FixPhotoPathFormat` (`fix:photo-paths`)
- `TailLogs` (`logs:tail`)

## 6) Estado observado durante el análisis (sin levantar proyecto)

Este análisis fue estático, sin ejecutar la app completa. Se observó:

- El repositorio estaba limpio en git al momento de revisar.
- No existía carpeta `vendor` en ese momento, por lo que no se pudo correr `php artisan` ni tests.

## 7) Observaciones técnicas a tener presentes

- Existe `routes/api.php` con endpoints de Rekognition (`/api/v1/...`) y middleware `auth:sanctum`.
- En `bootstrap/app.php` solo se registran rutas web/console, no se observó registro explícito de `routes/api.php` en el estado revisado.
- No se observó `laravel/sanctum` en `composer.json` revisado, pese a que la API usa `auth:sanctum`.
- En API, la validación de `collection_id` usa `unique:collections`; la tabla del proyecto es `rekognition_collections`.
- En una parte de Filament se usa `face_id` singular al guardar resultado de indexación, mientras el servicio retorna `face_ids` (array). Conviene alinear.

## 8) Convenciones actuales de negocio

- El enlace público de validación se resuelve por `Institution.uuid`.
- La correspondencia persona <-> rostro en validación depende de `external_image_id` en Rekognition, normalmente basado en nombre de archivo.
- El `document_number` de la persona se infiere desde el nombre del archivo (sin extensión) al validar coincidencias.
- Los logs de validación se guardan siempre con `institution_id`, `document_number`, `similarity`, `matched`, `validated_at`.

## 9) Archivos clave de referencia rápida

- `app/Services/RekognitionService.php`
- `app/Http/Controllers/Validate/ValidateController.php`
- `app/Filament/Pages/CollectionsPage.php`
- `app/Filament/Resources/Institutions/InstitutionResource.php`
- `app/Filament/Resources/Institutions/Pages/PeopleRelationManager.php`
- `app/Imports/PeopleImport.php`
- `app/Models/Institution.php`
- `app/Models/People.php`
- `app/Models/ValidationLog.php`
- `app/Models/RekognitionCollection.php`
- `app/Models/RekognitionIndexedImage.php`
- `routes/web.php`
- `routes/api.php`
- `config/aws.php`
- `config/filesystems.php`

## 10) Política rápida de secretos (git)

- No subir nunca valores reales de credenciales (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, tokens, passwords).
- Usar siempre `.env` local para secretos.
- Confirmar antes de cada commit que no se incluyeron secretos en archivos `.md`, scripts o código.
- En este `context.md` solo deben existir nombres de variables y configuración referencial, nunca valores sensibles.
