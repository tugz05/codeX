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
        Schema::create('question_answers', function (Blueprint $table) {
            $table->id();
            
            // Link to question
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            
            // Polymorphic relation: can belong to quiz_attempt or exam_attempt
            $table->morphs('attemptable'); // creates attemptable_id and attemptable_type
            
            // Answer details
            $table->json('answer')->nullable(); // Store answer(s) as JSON
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points_earned')->default(0);
            $table->text('feedback')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_answers');
    }
};
