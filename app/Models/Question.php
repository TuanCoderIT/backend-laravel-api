<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'options',
        'answer',
        'explanation',
        'type',
        'points',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function exams()
    {
        return $this->belongsToMany(Exam::class)->withPivot('order');
    }
}
