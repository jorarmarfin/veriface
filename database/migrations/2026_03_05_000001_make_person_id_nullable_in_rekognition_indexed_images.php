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
        // Solo modificar si la tabla existe y la columna ya está creada
        if (Schema::hasTable('rekognition_indexed_images') && Schema::hasColumn('rekognition_indexed_images', 'person_id')) {
            Schema::table('rekognition_indexed_images', function (Blueprint $table) {
                // Cambiar person_id a nullable
                $table->foreignId('person_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rekognition_indexed_images') && Schema::hasColumn('rekognition_indexed_images', 'person_id')) {
            Schema::table('rekognition_indexed_images', function (Blueprint $table) {
                // Revertir person_id a no nullable
                $table->foreignId('person_id')->nullable(false)->change();
            });
        }
    }
};

