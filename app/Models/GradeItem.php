<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_component_id',
        'name',
        'max_points',
        'date',
        'description',
        'order',
    ];

    protected $casts = [
        'max_points' => 'decimal:2',
        'date' => 'date',
        'order' => 'integer',
    ];

    /**
     * Get the grade component that owns this item
     */
    public function gradeComponent()
    {
        return $this->belongsTo(GradeComponent::class);
    }

    /**
     * Get the student grades for this item
     */
    public function studentGrades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    /**
     * Get a specific student's grade for this item
     */
    public function getStudentGrade($userId)
    {
        return $this->studentGrades()->where('user_id', $userId)->first();
    }

    /**
     * Calculate average score for this item
     */
    public function getAverageScore()
    {
        $avg = $this->studentGrades()->avg('points');
        return $avg ? round($avg, 2) : 0;
    }

    /**
     * Get percentage of students who passed (>= 60%)
     */
    public function getPassingRate()
    {
        $total = $this->studentGrades()->count();
        if ($total == 0) {
            return 0;
        }

        $passing = $this->studentGrades()
            ->whereRaw('(points / ?) >= 0.6', [$this->max_points])
            ->count();

        return round(($passing / $total) * 100, 2);
    }
}
