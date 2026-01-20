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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('assignment_created_email')->default(true);
            $table->boolean('assignment_created_in_app')->default(true);
            $table->boolean('quiz_created_email')->default(true);
            $table->boolean('quiz_created_in_app')->default(true);
            $table->boolean('grade_released_email')->default(true);
            $table->boolean('grade_released_in_app')->default(true);
            $table->boolean('due_date_reminder_email')->default(true);
            $table->boolean('due_date_reminder_in_app')->default(true);
            $table->boolean('announcement_email')->default(true);
            $table->boolean('announcement_in_app')->default(true);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
