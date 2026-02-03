<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_item_id',
        'user_id',
        'points',
        'remarks',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    /**
     * Get the grade item that owns this grade
     */
    public function gradeItem()
    {
        return $this->belongsTo(GradeItem::class);
    }

    /**
     * Get the student who received this grade
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the instructor who graded this
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Calculate percentage score
     */
    public function getPercentageAttribute()
    {
        if (!$this->gradeItem || $this->gradeItem->max_points == 0) {
            return 0;
        }

        return round(($this->points / $this->gradeItem->max_points) * 100, 2);
    }

    /**
     * Check if passing (>= 60%)
     */
    public function isPassing()
    {
        return $this->percentage >= 60;
    }

    /**
     * Get letter grade (optional)
     */
    public function getLetterGrade()
    {
        $percentage = $this->percentage;

        if ($percentage >= 97) return 'A+';
        if ($percentage >= 93) return 'A';
        if ($percentage >= 90) return 'A-';
        if ($percentage >= 87) return 'B+';
        if ($percentage >= 83) return 'B';
        if ($percentage >= 80) return 'B-';
        if ($percentage >= 77) return 'C+';
        if ($percentage >= 73) return 'C';
        if ($percentage >= 70) return 'C-';
        if ($percentage >= 67) return 'D+';
        if ($percentage >= 63) return 'D';
        if ($percentage >= 60) return 'D-';
        return 'F';
    }
}
