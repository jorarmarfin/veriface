<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekognition_collections', function (Blueprint $table) {
            $table->id();
            // Nombre interno que usas en AWS (CollectionId)
            $table->string('collection_id')->unique();
            // Nombre amigable para mostrar en UI
            $table->string('name');
            // Región AWS donde fue creada
            $table->string('region')->default('eu-north-1');
            // ARN devuelto por AWS
            $table->string('collection_arn')->nullable();
            // Versión del modelo facial
            $table->string('face_model_version')->nullable();
            // Cantidad de rostros indexados (opcional, para métricas rápidas)
            $table->unsignedInteger('faces_count')->default(0);
            // Activa o no (por si quieres deshabilitar sin borrar en AWS)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekognition_collections');
    }
};
