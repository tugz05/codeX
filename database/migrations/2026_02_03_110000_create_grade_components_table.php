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
        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();
            $table->string('classlist_id', 15);
            $table->string('name'); // e.g., "Assignments", "Quizzes", "Exams"
            $table->decimal('weight', 5, 2); // Percentage weight (e.g., 30.00 for 30%)
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Display order
            $table->timestamps();

            $table->foreign('classlist_id')
                ->references('id')
                ->on('classlists')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_components');
    }
};
