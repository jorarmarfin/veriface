<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // validation_logs: tabla más consultada del sistema
        Schema::table('validation_logs', function (Blueprint $table) {
            // filtros simples frecuentes
            $table->index('document_number', 'vl_document_number_idx');
            $table->index('matched', 'vl_matched_idx');
            $table->index('validated_at', 'vl_validated_at_idx');
            $table->index('similarity', 'vl_similarity_idx');

            // compuestos para widgets de estadísticas (whereDate+matched, whereBetween+matched)
            $table->index(['institution_id', 'validated_at'], 'vl_institution_date_idx');
            $table->index(['institution_id', 'matched'], 'vl_institution_matched_idx');

            // historial de un DNI dentro de una institución
            $table->index(['institution_id', 'document_number'], 'vl_institution_document_idx');
        });

        // institutions: filtro de activas en dashboard y controller
        Schema::table('institutions', function (Blueprint $table) {
            $table->index('is_active', 'inst_is_active_idx');
        });

        // people: búsqueda de DNI sin filtro de institución (scopeByDocumentNumber)
        Schema::table('people', function (Blueprint $table) {
            $table->index('document_number', 'ppl_document_number_idx');
        });

        // rekognition_indexed_images: orderByDesc('indexed_at') y búsqueda por face_id+collection
        Schema::table('rekognition_indexed_images', function (Blueprint $table) {
            $table->index('indexed_at', 'rii_indexed_at_idx');
            $table->index(['face_id', 'rekognition_collection_id'], 'rii_face_collection_idx');
            $table->index(['rekognition_collection_id', 'is_active'], 'rii_collection_active_idx');
        });

        // rekognition_collections: filtro de colecciones activas
        Schema::table('rekognition_collections', function (Blueprint $table) {
            $table->index('is_active', 'rc_is_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->dropIndex('vl_document_number_idx');
            $table->dropIndex('vl_matched_idx');
            $table->dropIndex('vl_validated_at_idx');
            $table->dropIndex('vl_similarity_idx');
            $table->dropIndex('vl_institution_date_idx');
            $table->dropIndex('vl_institution_matched_idx');
            $table->dropIndex('vl_institution_document_idx');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropIndex('inst_is_active_idx');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('ppl_document_number_idx');
        });

        Schema::table('rekognition_indexed_images', function (Blueprint $table) {
            $table->dropIndex('rii_indexed_at_idx');
            $table->dropIndex('rii_face_collection_idx');
            $table->dropIndex('rii_collection_active_idx');
        });

        Schema::table('rekognition_collections', function (Blueprint $table) {
            $table->dropIndex('rc_is_active_idx');
        });
    }
};
