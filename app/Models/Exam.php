<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'difficulty',
        'duration',
        'color',
        'passing_score',
        'max_attempts',
        'learning_objectives',
        'prerequisites',
        'tags',
        'status',
    ];
    protected $casts = [
        'learning_objectives' => 'array',
        'prerequisites' => 'array',
        'tags' => 'array',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class)->withPivot('order');
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
