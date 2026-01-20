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
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('activity_created_email')->default(true)->after('announcement_in_app');
            $table->boolean('activity_created_in_app')->default(true)->after('activity_created_email');
            $table->boolean('material_created_email')->default(true)->after('activity_created_in_app');
            $table->boolean('material_created_in_app')->default(true)->after('material_created_email');
            $table->boolean('examination_created_email')->default(true)->after('material_created_in_app');
            $table->boolean('examination_created_in_app')->default(true)->after('examination_created_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            //
        });
    }
};
