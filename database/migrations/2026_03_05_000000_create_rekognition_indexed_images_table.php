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
        Schema::create('rekognition_indexed_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('rekognition_collection_id')->constrained('rekognition_collections')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->cascadeOnDelete();
            $table->string('face_id'); // ID del rostro en AWS Rekognition
            $table->string('image_path'); // Ruta del archivo de imagen
            $table->string('image_name'); // Nombre del archivo
            $table->decimal('confidence', 5, 2)->nullable(); // Confianza del rostro detectado
            $table->json('face_details')->nullable(); // Detalles del rostro (JSON)
            $table->boolean('is_active')->default(true);
            $table->timestamp('indexed_at')->nullable(); // Fecha cuando se indexó
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('rekognition_collection_id');
            $table->index('person_id');
            $table->index('face_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekognition_indexed_images');
    }
};

