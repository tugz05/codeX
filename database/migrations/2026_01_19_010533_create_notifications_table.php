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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable'); // user_id and notifiable_type
            $table->text('data'); // JSON data
            $table->timestamp('read_at')->nullable();
            $table->string('type_key'); // assignment_created, quiz_created, grade_released, due_date_reminder, announcement
            $table->morphs('related'); // related_id and related_type (assignment, quiz, etc.)
            $table->string('classlist_id', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
