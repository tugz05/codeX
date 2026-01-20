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
        Schema::create('file_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()
                ->constrained('file_folders')
                ->nullOnDelete()
                ->name('file_folders_parent_id_foreign');
            $table->morphs('folderable'); // assignment_submission, activity_submission, etc.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Add folder_id to attachment tables
        Schema::table('assignment_submission_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignment_submission_attachments', 'folder_id')) {
                $table->foreignId('folder_id')->nullable()->after('is_current')
                    ->constrained('file_folders')
                    ->nullOnDelete()
                    ->name('as_attachments_folder_id_foreign');
            }
        });

        if (Schema::hasTable('activity_submission_attachments')) {
            Schema::table('activity_submission_attachments', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_submission_attachments', 'folder_id')) {
                    $table->foreignId('folder_id')->nullable()->after('is_current')
                        ->constrained('file_folders')
                        ->nullOnDelete()
                        ->name('as_activity_attachments_folder_id_foreign');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_submission_attachments', function (Blueprint $table) {
            $table->dropForeign('as_attachments_folder_id_foreign');
            $table->dropColumn('folder_id');
        });

        if (Schema::hasTable('activity_submission_attachments')) {
            Schema::table('activity_submission_attachments', function (Blueprint $table) {
                $table->dropForeign('as_activity_attachments_folder_id_foreign');
                $table->dropColumn('folder_id');
            });
        }

        Schema::dropIfExists('file_folders');
    }
};
