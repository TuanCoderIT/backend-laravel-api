<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseUnlock extends Model
{
    protected $table = 'course_unlocks';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'unlocked_at',
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
