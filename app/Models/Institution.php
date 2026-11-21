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
        'event',
        'slug',
        'filepath',
        'rekognition_collection_id',
        'is_active',
        'api_key_hash',
        'api_key_generated_at',
        'validations_contracted',
        'validations_used',
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
            'api_key_generated_at' => 'datetime',
            'validations_contracted' => 'integer',
            'validations_used' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $hidden = ['api_key_hash'];

    /**
     * Genera una nueva api_key. Devuelve el valor en claro (solo se muestra una vez);
     * en BD se guarda únicamente su hash SHA-256.
     */
    public function generateApiKey(): string
    {
        $plain = 'vf_' . \Illuminate\Support\Str::random(40);

        $this->forceFill([
            'api_key_hash' => hash('sha256', $plain),
            'api_key_generated_at' => now(),
        ])->save();

        return $plain;
    }

    public function revokeApiKey(): void
    {
        $this->forceFill(['api_key_hash' => null, 'api_key_generated_at' => null])->save();
    }

    public function checkApiKey(string $plain): bool
    {
        return $plain !== ''
            && $this->api_key_hash !== null
            && hash_equals($this->api_key_hash, hash('sha256', $plain));
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
     * Obtener todas las imágenes indexadas de esta institución (a través de su colección)
     */
    public function indexedImages(): HasMany
    {
        return $this->hasMany(RekognitionIndexedImage::class, 'rekognition_collection_id', 'rekognition_collection_id');
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
     * Determina si la institución tiene límite de validaciones.
     */
    public function hasValidationLimit(): bool
    {
        return $this->validations_contracted !== null;
    }

    /**
     * Determina si ya alcanzó el límite de validaciones contratadas.
     */
    public function isValidationQuotaExceeded(): bool
    {
        if (!$this->hasValidationLimit()) {
            return false;
        }

        return $this->validations_used >= $this->validations_contracted;
    }

    /**
     * Cantidad restante de validaciones.
     * null significa ilimitado.
     */
    public function getValidationsRemainingAttribute(): ?int
    {
        if (!$this->hasValidationLimit()) {
            return null;
        }

        return max(0, $this->validations_contracted - $this->validations_used);
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
