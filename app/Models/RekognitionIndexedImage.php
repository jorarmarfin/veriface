<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekognitionIndexedImage extends Model
{
    protected $table = 'rekognition_indexed_images';

    protected $fillable = [
        'uuid',
        'rekognition_collection_id',
        'person_id',
        'face_id',
        'image_path',
        'image_name',
        'confidence',
        'face_details',
        'is_active',
        'indexed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'confidence' => 'decimal:2',
        'face_details' => 'array',
        'indexed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con RekognitionCollection
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(RekognitionCollection::class, 'rekognition_collection_id');
    }

    /**
     * Relación con People
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }
}

