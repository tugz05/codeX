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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            
            // Link to classlist
            $table->string('classlist_id', 15);
            $table->foreign('classlist_id')
                  ->references('id')->on('classlists')
                  ->cascadeOnDelete();
            
            // Who created
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Quiz details
            $table->string('title');
            $table->longText('description')->nullable();
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('time_limit')->nullable(); // in minutes
            $table->unsignedInteger('attempts_allowed')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_published')->default(false);
            
            // Schedule
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
