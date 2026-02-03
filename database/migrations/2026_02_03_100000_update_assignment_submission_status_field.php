<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing records to use new status values
        DB::table('assignment_submissions')
            ->where('status', 'draft')
            ->whereNotNull('submitted_at')
            ->update(['status' => 'submitted']);

        // Change the status enum to include all new statuses
        Schema::table('assignment_submissions', function (Blueprint $table) {
            // Drop the old enum
            $table->dropColumn('status');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            // Add the new enum with all statuses
            $table->enum('status', [
                'assigned',      // Work to be done (default when created)
                'turned_in',     // Submitted on time
                'missing',       // Past due, not submitted
                'late',          // Submitted after deadline
                'graded',        // Graded/Returned by teacher
            ])->default('assigned')->after('video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'graded'])->default('draft');
        });
    }
};
