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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            // anchor to classlists
            $table->string('classlist_id', 15);
            $table->foreign('classlist_id')
                  ->references('id')->on('classlists')
                  ->cascadeOnDelete();

            // who posted
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();

            // assignment/announcement details
            $table->string('title');
            $table->longText('instruction')->nullable();
            $table->unsignedInteger('points')->nullable();

            // schedule fields
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->date('accessible_date')->nullable();
            $table->time('accessible_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
