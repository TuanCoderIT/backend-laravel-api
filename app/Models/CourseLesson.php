<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;


// class CourseLesson extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'chapter_id',
//         'title',
//         'content',
//         'type',
//         'video_url',
//         'duration_seconds',
//         'is_free_preview',
//         'order',
//     ];

//     public function chapter()
//     {
//         return $this->belongsTo(CourseChapter::class);
//     }

//     public function course()
//     {
//         return $this->chapter->course();
//     }

//     public function progress()
//     {
//         return $this->hasMany(CourseProgress::class, 'lesson_id');
//     }

//     public function unlocks()
//     {
//         return $this->hasMany(CourseUnlock::class, 'lesson_id');
//     }
// }
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'title',
        'content',
        'type',
        'video_url',
        'duration_seconds',
        'is_free_preview',
        'order',
    ];

    // Lesson belongs to a chapter
    public function chapter()
    {
        return $this->belongsTo(CourseChapter::class, 'chapter_id');
    }

    // Lesson belongs to a course (helper relation, không dùng join)
    public function course()
    {
        return $this->chapter->course();
    }

    // Progress / Unlocks
    public function progress()
    {
        return $this->hasMany(CourseProgress::class, 'lesson_id');
    }

    public function unlocks()
    {
        return $this->hasMany(CourseUnlock::class, 'lesson_id');
    }
}
