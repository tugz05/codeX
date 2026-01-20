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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation: can belong to Activity, Assignment, Material, ActivitySubmission, AssignmentSubmission
            $table->morphs('commentable'); // creates commentable_id and commentable_type
            
            // Who made the comment
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Comment content
            $table->text('content');
            
            // Optional: Reply to another comment (for threaded discussions)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            
            // Optional: Classlist context (for filtering)
            $table->string('classlist_id', 15)->nullable();
            $table->foreign('classlist_id')->references('id')->on('classlists')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->index(['commentable_id', 'commentable_type']);
            $table->index('user_id');
            $table->index('classlist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
