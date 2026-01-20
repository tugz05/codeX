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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation: can belong to quiz or examination
            $table->morphs('testable'); // creates testable_id and testable_type
            
            // Test details
            $table->string('title'); // e.g., "Test I - Identification"
            $table->string('type')->nullable(); // e.g., "identification", "true_false", "essay"
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('total_points')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
