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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            // UUID público para usar en URL
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('slug')->unique()->nullable();

            $table->string('filepath')->nullable();

            //rekognition_collections
            $table->foreignId('rekognition_collection_id')
                ->nullable()
                ->constrained('rekognition_collections')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
