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
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'url')) {
                $table->string('url')->nullable()->after('type');
            }
            if (!Schema::hasColumn('materials', 'video_url')) {
                $table->string('video_url')->nullable()->after('url');
            }
            if (!Schema::hasColumn('materials', 'embed_code')) {
                $table->longText('embed_code')->nullable()->after('video_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['url', 'video_url', 'embed_code']);
        });
    }
};
