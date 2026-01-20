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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            // Anchor to classlists
            $table->string('classlist_id', 15);
            $table->foreign('classlist_id')
                  ->references('id')->on('classlists')
                  ->cascadeOnDelete();

            // Who posted
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();

            // Assignment details
            $table->string('title');
            $table->longText('instruction')->nullable();
            $table->integer('points')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->date('accessible_date')->nullable();
            $table->time('accessible_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
