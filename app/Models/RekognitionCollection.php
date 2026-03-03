<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekognitionCollection extends Model
{
    protected $table = 'rekognition_collections';

    protected $fillable = [
        'collection_id',
        'name',
        'region',
        'collection_arn',
        'face_model_version',
        'faces_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'faces_count' => 'integer',
    ];
}

