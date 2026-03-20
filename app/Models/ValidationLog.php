<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationLog extends Model
{
    /**
     * Los atributos que son asignables en masa
     *
     * @var array
     */
    protected $fillable = [
        'institution_id',
        'document_number',
        'similarity',
        'matched',
        'validated_at',
        'response',
    ];

    /**
     * Obtener los atributos que deben ser convertidos
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'similarity' => 'decimal:2',
            'matched' => 'boolean',
            'validated_at' => 'datetime',
            'response' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Obtener la institución asociada a este registro de validación
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Obtener la persona asociada a este registro de validación
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class, 'document_number', 'document_number');
    }

    /**
     * Scope para obtener validaciones exitosas
     */
    public function scopeMatched($query)
    {
        return $query->where('matched', true);
    }

    /**
     * Scope para obtener validaciones fallidas
     */
    public function scopeFailed($query)
    {
        return $query->where('matched', false);
    }

    /**
     * Scope para obtener validaciones de una institución
     */
    public function scopeByInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope para obtener validaciones de una persona
     */
    public function scopeByDocumentNumber($query, $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    /**
     * Scope para obtener validaciones en un rango de fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('validated_at', [$startDate, $endDate]);
    }

    /**
     * Scope para obtener validaciones con similitud mayor a un valor
     */
    public function scopeHighSimilarity($query, $threshold = 80)
    {
        return $query->where('similarity', '>=', $threshold);
    }

    /**
     * Obtener el porcentaje de éxito de validaciones de una institución
     */
    public function scopeSuccessRateByInstitution($query, $institutionId)
    {
        $total = $query->where('institution_id', $institutionId)->count();

        if ($total === 0) {
            return 0;
        }

        $successful = $query->where('institution_id', $institutionId)
            ->where('matched', true)
            ->count();

        return round(($successful / $total) * 100, 2);
    }
}
