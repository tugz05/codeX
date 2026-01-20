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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            // Anchor to classlists
            $table->string('classlist_id', 15);
            $table->foreign('classlist_id')
                  ->references('id')->on('classlists')
                  ->cascadeOnDelete();

            // Who posted
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();

            // Material details
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('type')->default('resource'); // resource, link, document, video, other
            $table->string('url')->nullable(); // For link type
            $table->string('video_url')->nullable(); // For video type
            $table->longText('embed_code')->nullable(); // For video embed code

            // Schedule fields (optional - when material becomes available)
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
        Schema::dropIfExists('materials');
    }
};
