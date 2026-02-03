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
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_component_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Quiz 1", "Midterm Exam"
            $table->decimal('max_points', 8, 2); // Maximum possible points
            $table->date('date')->nullable(); // Date administered
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Display order within component
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
