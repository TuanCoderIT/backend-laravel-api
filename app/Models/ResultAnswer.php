<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'result_id',
        'user_id',
        'exam_id',
        'question_id',
        'user_answer',
        'correct_answer',
        'is_correct',
        'points',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Kết quả bài làm
    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    // Người làm bài
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Bài quiz/exam
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Câu hỏi
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}