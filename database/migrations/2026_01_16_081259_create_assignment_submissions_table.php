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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('classlist_id', 15);
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('submission_type', ['file', 'link', 'video_link'])->default('file');
            $table->string('link_url')->nullable(); // For link type
            $table->string('video_url')->nullable(); // For video_link type
            $table->enum('status', ['draft', 'submitted', 'graded'])->default('draft');
            $table->unsignedInteger('score')->nullable();
            $table->text('feedback')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'user_id']);
            $table->foreign('classlist_id')->references('id')->on('classlists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
