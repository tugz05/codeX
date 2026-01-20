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
        Schema::table('assignment_submission_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignment_submission_attachments', 'version')) {
                $table->integer('version')->default(1)->after('size');
            }
            if (!Schema::hasColumn('assignment_submission_attachments', 'parent_attachment_id')) {
                $table->foreignId('parent_attachment_id')->nullable()->after('version')
                    ->constrained('assignment_submission_attachments')
                    ->nullOnDelete()
                    ->name('as_attachments_parent_id_foreign');
            }
            if (!Schema::hasColumn('assignment_submission_attachments', 'version_notes')) {
                $table->text('version_notes')->nullable()->after('parent_attachment_id');
            }
            if (!Schema::hasColumn('assignment_submission_attachments', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('version_notes');
            }
        });

        // Only add to activity_submission_attachments if table exists
        if (Schema::hasTable('activity_submission_attachments')) {
            Schema::table('activity_submission_attachments', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_submission_attachments', 'version')) {
                    $table->integer('version')->default(1)->after('size');
                }
                if (!Schema::hasColumn('activity_submission_attachments', 'parent_attachment_id')) {
                    $table->foreignId('parent_attachment_id')->nullable()->after('version')
                        ->constrained('activity_submission_attachments')
                        ->nullOnDelete()
                        ->name('as_activity_attachments_parent_id_foreign');
                }
                if (!Schema::hasColumn('activity_submission_attachments', 'version_notes')) {
                    $table->text('version_notes')->nullable()->after('parent_attachment_id');
                }
                if (!Schema::hasColumn('activity_submission_attachments', 'is_current')) {
                    $table->boolean('is_current')->default(true)->after('version_notes');
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
            $table->dropForeign('as_attachments_parent_id_foreign');
            $table->dropColumn(['version', 'parent_attachment_id', 'version_notes', 'is_current']);
        });

        if (Schema::hasTable('activity_submission_attachments')) {
            Schema::table('activity_submission_attachments', function (Blueprint $table) {
                $table->dropForeign('as_activity_attachments_parent_id_foreign');
                $table->dropColumn(['version', 'parent_attachment_id', 'version_notes', 'is_current']);
            });
        }
    }
};
