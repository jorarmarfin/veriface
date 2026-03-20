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
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->json('response')->nullable()->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->dropColumn('response');
        });
    }
};

