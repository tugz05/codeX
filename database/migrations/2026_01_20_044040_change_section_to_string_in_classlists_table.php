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
        Schema::table('classlists', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['section_id']);
            // Drop the section_id column
            $table->dropColumn('section_id');
            // Add section as a string column
            $table->string('section')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classlists', function (Blueprint $table) {
            // Remove section column
            $table->dropColumn('section');
            // Add back section_id with foreign key
            $table->foreignId('section_id')->constrained()->cascadeOnDelete()->after('user_id');
        });
    }
};
