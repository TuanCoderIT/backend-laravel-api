<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    protected $table = 'course_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'watched_seconds',
        'scroll_percent',
        'is_completed',
    ];

    protected $casts = [
        'watched_seconds' => 'integer',
        'scroll_percent'  => 'integer',
        'is_completed'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
