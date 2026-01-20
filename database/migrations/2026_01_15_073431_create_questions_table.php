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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation: can belong to quiz or examination
            $table->morphs('questionable'); // creates questionable_id and questionable_type
            
            // Question details
            $table->text('question_text');
            $table->enum('type', ['multiple_choice', 'true_false', 'short_answer', 'essay'])->default('multiple_choice');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('order')->default(0);
            
            // For multiple choice: store options as JSON
            $table->json('options')->nullable(); // ['option1', 'option2', 'option3', 'option4']
            
            // Correct answer(s)
            $table->json('correct_answer')->nullable(); // For multiple choice: ['A'], for true/false: ['true'], for short answer: ['answer1', 'answer2']
            
            // Additional settings
            $table->text('explanation')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
