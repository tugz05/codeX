<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'classlist_id',
        'name',
        'weight',
        'description',
        'order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'order' => 'integer',
    ];

    /**
     * Get the classlist that owns the grade component
     */
    public function classlist()
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    /**
     * Get the grade items for this component
     */
    public function gradeItems()
    {
        return $this->hasMany(GradeItem::class)->orderBy('order');
    }

    /**
     * Calculate total max points for this component
     */
    public function getTotalMaxPointsAttribute()
    {
        return $this->gradeItems()->sum('max_points');
    }

    /**
     * Get student's total points for this component
     */
    public function getStudentTotalPoints($userId)
    {
        return StudentGrade::whereHas('gradeItem', function ($query) {
            $query->where('grade_component_id', $this->id);
        })
        ->where('user_id', $userId)
        ->sum('points');
    }

    /**
     * Get student's percentage for this component
     */
    public function getStudentPercentage($userId)
    {
        $totalMax = $this->total_max_points;
        if ($totalMax == 0) {
            return 0;
        }

        $studentPoints = $this->getStudentTotalPoints($userId);
        return ($studentPoints / $totalMax) * 100;
    }
}
