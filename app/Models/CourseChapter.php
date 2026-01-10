<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class CourseChapter extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'course_id',
//         'title',
//         'description',
//         'order',
//     ];

//     public function course()
//     {
//         return $this->belongsTo(Course::class);
//     }

//     public function lessons()
//     {
//         return $this->hasMany(CourseLesson::class, 'chapter_id')->orderBy('order');
//     }
// }
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseChapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];

    // Chapter belongs to a course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Chapter has many lessons
    public function lessons()
    {
        return $this->hasMany(CourseLesson::class, 'chapter_id')->orderBy('order');
    }
}
