<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class People extends Model
{
    /**
     * Los atributos que son asignables en masa
     *
     * @var array
     */
    protected $fillable = [
        'institution_id',
        'document_number',
        'names',
        'photo_path',
        'metadata',
    ];

    /**
     * Obtener los atributos que deben ser convertidos
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Obtener la institución a la que pertenece esta persona
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Obtener todos los registros de validación de esta persona
     */
    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class, 'document_number', 'document_number')
            ->where('institution_id', $this->institution_id);
    }

    /**
     * Scope para obtener personas por número de documento
     */
    public function scopeByDocumentNumber($query, $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    /**
     * Scope para obtener personas de una institución
     */
    public function scopeByInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope para buscar personas por nombre
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('names', 'like', "%{$name}%");
    }

    /**
     * Obtener el porcentaje promedio de similitud en validaciones
     */
    public function getAverageSimilarityAttribute(): ?float
    {
        $average = $this->validationLogs()
            ->whereNotNull('similarity')
            ->average('similarity');

        return $average ? round($average, 2) : null;
    }

    /**
     * Obtener el total de validaciones exitosas
     */
    public function getSuccessfulValidationsAttribute(): int
    {
        return $this->validationLogs()
            ->where('matched', true)
            ->count();
    }

    /**
     * Obtener el total de validaciones
     */
    public function getTotalValidationsAttribute(): int
    {
        return $this->validationLogs()->count();
    }
}
