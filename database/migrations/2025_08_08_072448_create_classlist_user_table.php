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
        Schema::create('classlist_user', function (Blueprint $table) {
            $table->id();
            $table->string('classlist_id', 15);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps(); // created_at and updated_at
            $table->enum('status', ['active', 'unenroll', 'archive'])->default('active');
            $table->timestamp('joined_at')->nullable();

            $table->foreign('classlist_id')->references('id')->on('classlists')->onDelete('cascade');
            $table->unique(['classlist_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classlist_user');
    }
};
