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
        Schema::create('activity_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('classlist_id', 15);
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('language', 32)->nullable();          // e.g. 'python', 'cpp', 'java'
            $table->longText('code')->nullable();                 // student solution
            $table->enum('status', ['draft', 'submitted', 'graded', 'missing'])->default('draft');
            $table->unsignedInteger('score')->nullable();         // optional numeric score
            $table->text('feedback')->nullable();                 // AI/manual feedback JSON or text
            $table->integer('runtime_ms')->nullable();            // optional metrics
            $table->integer('memory_kb')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'user_id']);
            $table->foreign('classlist_id')->references('id')->on('classlists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_submissions');
    }
};
