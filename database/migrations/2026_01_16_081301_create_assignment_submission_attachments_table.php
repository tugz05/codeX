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
        Schema::create('assignment_submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')
                ->constrained('assignment_submissions')
                ->cascadeOnDelete()
                ->name('as_attachments_submission_id_foreign');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('url');
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submission_attachments');
    }
};
