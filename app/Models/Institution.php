<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Institution extends Model
{
    /**
     * Los atributos que son asignables en masa
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'filepath',
        'rekognition_collection_id',
        'is_active',
    ];

    /**
     * Obtener los atributos que deben ser convertidos
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Obtener todos los registros de personas de esta institución
     */
    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    //relation with rekognition_collection
    public function rekognitionCollection(): BelongsTo
    {
        return $this->belongsTo(RekognitionCollection::class);
    }

    /**
     * Obtener todos los registros de validación de esta institución
     */
    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    /**
     * Scope para obtener solo instituciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para buscar por slug
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug)->first();
    }

    /**
     * Genera el slug y UUID automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generar UUID si no existe
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }

            // Generar slug si no existe
            if (empty($model->slug)) {
                $model->slug = \Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            // Actualizar slug si el nombre cambió
            if ($model->isDirty('name')) {
                $model->slug = \Str::slug($model->name);
            }
        });

        static::created(function ($model) {
            // Crear carpeta si filepath está definido
            if (!empty($model->filepath)) {
                $model->createStorageDirectory();
            }
        });

        static::updated(function ($model) {
            // Crear carpeta si filepath cambió
            if ($model->isDirty('filepath') && !empty($model->filepath)) {
                $model->createStorageDirectory();
            }
        });
    }

    /**
     * Crear el directorio de almacenamiento
     */
    public function createStorageDirectory(): bool
    {
        try {
            $basePath = storage_path('app/public');
            $fullPath = $basePath . '/' . ltrim($this->filepath, '/');

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                \Log::info("Directorio creado para institución: {$this->name}", [
                    'filepath' => $this->filepath,
                    'full_path' => $fullPath,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Error al crear directorio para institución: {$this->name}", [
                'filepath' => $this->filepath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }






}
