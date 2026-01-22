<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_messages')) {
            return;
        }

        Schema::create('class_messages', function (Blueprint $table) {
            $table->id();
            $table->string('classlist_id');
            $table->foreign('classlist_id')->references('id')->on('classlists')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['classlist_id', 'sender_id']);
            $table->index(['classlist_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_messages');
    }
};
