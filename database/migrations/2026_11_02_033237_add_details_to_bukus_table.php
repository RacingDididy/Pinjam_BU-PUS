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
        Schema::table('bukus', function (Blueprint $table) {
            $table->unsignedInteger('tebal_halaman')->nullable()->default(350)->after('tahun_terbit');
            $table->string('bahasa')->default('Indonesia')->after('tebal_halaman');
            $table->json('gallery_images')->nullable()->after('cover_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->dropColumn(['tebal_halaman', 'bahasa', 'gallery_images']);
        });
    }
};
