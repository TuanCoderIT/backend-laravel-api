<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Str;

// class Course extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'user_id',
//         'category_id',
//         'title',
//         'slug',
//         'description',
//         'thumbnail',
//         'is_public',
//     ];

//     // --- Relations ---
//     public function user()
//     {
//         return $this->belongsTo(User::class, 'user_id');
//     }

//     public function category()
//     {
//         return $this->belongsTo(Category::class);
//     }

//     public function chapters()
//     {
//         return $this->hasMany(CourseChapter::class)
//             ->orderBy('order')
//             ->with('lessons');
//     }

//     // --- Mutators / Accessors ---
//     protected static function booted()
//     {
//         static::creating(function ($course) {
//             if (empty($course->slug)) {
//                 $course->slug = Str::slug($course->title) . '-' . Str::random(5);
//             }
//         });
//     }

//     public function lessons()
//     {
//         return $this->hasManyThrough(CourseLesson::class, CourseChapter::class);
//     }

//     public function lessonsCount()
//     {
//         return $this->lessons()->count();
//     }

//     // public function getRouteKeyName(): string
//     // {
//     //     return 'slug'; // <-- Thiết lập tìm kiếm bằng cột 'slug'
//     // }
// }
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia; // <-- IMPORT Trait
use Spatie\MediaLibrary\InteractsWithMedia; // <-- IMPORT Trait

class Course extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'is_public',
    ];

    // ---------- Relations ----------

    // Course belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Course belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Course has many chapters
    public function chapters()
    {
        return $this->hasMany(CourseChapter::class)
            ->orderBy('order')
            ->with('lessons'); // eager load lessons
    }

    // Course has many lessons through chapters
    public function lessons()
    {
        return $this->hasManyThrough(
            CourseLesson::class,   // final model
            CourseChapter::class,  // through model
            'course_id',           // foreign key on CourseChapter
            'chapter_id',          // foreign key on CourseLesson
            'id',                  // local key on Course
            'id'                   // local key on CourseChapter
        );
    }

    public function tokenPricing()
    {
        return $this->morphOne(TokenPricing::class, 'target');
    }

    public function lessonsCount()
    {
        return $this->lessons()->count();
    }

    // ---------- Mutators ----------

    protected static function booted()
    {
        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title) . '-' . Str::random(5);
            }
        });
    }
}
