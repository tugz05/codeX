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
        Schema::create('attempt_activities', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation: can belong to quiz_attempt or exam_attempt
            $table->morphs('attemptable'); // creates attemptable_id and attemptable_type
            
            // Activity details
            $table->string('activity_type'); // tab_switch, focus_blur, devtools, copy_paste, answer_change, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data (question_id, time_spent, etc.)
            
            // Timing
            $table->timestamp('occurred_at');
            
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['attemptable_id', 'attemptable_type', 'activity_type'], 'attempt_activities_composite_idx');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_activities');
    }
};
