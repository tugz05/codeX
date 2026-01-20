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
        // Check if instructor_id column doesn't exist and user_id exists
        // If user_id already exists (which it does), this migration is a no-op
        // since user_id already serves as the instructor reference
        if (Schema::hasColumn('classlists', 'user_id') && !Schema::hasColumn('classlists', 'instructor_id')) {
            // user_id already exists and serves as instructor_id, so no action needed
            // This migration is kept for compatibility but does nothing
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed on rollback
    }
};
