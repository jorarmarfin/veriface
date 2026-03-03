<?php
/*
|--------------------------------------------------------------------------
| AWS Configuration
|--------------------------------------------------------------------------
|
| Este archivo configura los servicios de AWS para la aplicación VeriFace.
| 
| Aunque la mayoría de la configuración viene de aws/aws-sdk-php-laravel,
| este archivo documenta la configuración específica para Rekognition.
|
*/
return [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'version' => 'latest',
    /*
    |--------------------------------------------------------------------------
    | AWS Rekognition Configuration
    |--------------------------------------------------------------------------
    */
    'rekognition' => [
        'version' => 'latest',
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        // Configuración específica de Rekognition
        'settings' => [
            // Thresholds por defecto
            'default_face_match_threshold' => 80,  // 80% de similitud
            'default_max_faces' => 5,               // Máximo 5 resultados
            // Atributos a detectar por defecto
            'default_detection_attributes' => ['DEFAULT'],
            // Configuración de colecciones
            'collections' => [
                'employees' => [
                    'description' => 'Colección de empleados',
                    'threshold' => 90  // Umbral alto para asistencia
                ],
                'visitors' => [
                    'description' => 'Colección de visitantes',
                    'threshold' => 85
                ],
                'suspects' => [
                    'description' => 'Colección de sospechosos',
                    'threshold' => 70  // Umbral bajo para búsqueda
                ]
            ]
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | S3 Configuration (para almacenar imágenes)
    |--------------------------------------------------------------------------
    */
    's3' => [
        'bucket' => env('AWS_BUCKET', 'veriface-faces'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => false,
    ],
];
