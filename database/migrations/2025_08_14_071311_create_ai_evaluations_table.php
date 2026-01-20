<?php
// database/migrations/2025_08_14_000000_create_ai_evaluations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            // Your submission model uses the default table "activity_submissions"
            $table->foreignId('submission_id')->constrained('activity_submissions')->cascadeOnDelete();
            $table->json('criteria_breakdown'); // [{id,name,weight,score,comment}, ...]
            $table->unsignedInteger('score')->nullable(); // 0..100
            $table->text('feedback')->nullable();         // short summary for student
            $table->json('model_raw')->nullable();        // raw JSON from Gemini
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ai_evaluations');
    }
};
