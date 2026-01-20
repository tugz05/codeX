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
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->json('annotations')->nullable()->after('feedback'); // For comment annotations
            $table->foreignId('criteria_id')->nullable()->after('annotations')->constrained()->nullOnDelete(); // For rubric-based grading
            $table->json('rubric_scores')->nullable()->after('criteria_id'); // Store rubric item scores
            $table->boolean('grade_override')->default(false)->after('rubric_scores'); // Flag for manual grade override
            $table->text('override_reason')->nullable()->after('grade_override'); // Reason for override
            $table->boolean('returned_to_student')->default(false)->after('override_reason'); // Track if returned with feedback
            $table->timestamp('returned_at')->nullable()->after('returned_to_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'annotations',
                'criteria_id',
                'rubric_scores',
                'grade_override',
                'override_reason',
                'returned_to_student',
                'returned_at',
            ]);
        });
    }
};
