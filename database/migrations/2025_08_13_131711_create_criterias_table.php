<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('criterias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // instructor owner
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('grading_method', ['sum','average','custom'])->default('sum');
            $table->timestamps();

            $table->unique(['user_id', 'title']);
        });

        Schema::create('criteria_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('criterias')->cascadeOnDelete();
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->nullable();   // used when grading_method = average/custom (0..100)
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('activity_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criterias')->cascadeOnDelete();
            $table->unsignedInteger('assigned_points')->nullable(); // optional override
            $table->timestamps();

            $table->unique(['activity_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_criteria');
        Schema::dropIfExists('criteria_items');
        Schema::dropIfExists('criterias');
    }
};
