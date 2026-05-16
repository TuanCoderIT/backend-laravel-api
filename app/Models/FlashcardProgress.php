<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlashcardProgress extends Model
{
    use HasFactory;

    protected $table = 'flashcard_progress';

    protected $fillable = [
        'user_id',
        'flashcard_id',
        'status',
        'correct_count',
        'review_count',
        'last_reviewed_at',
        'next_review_at',
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard()
    {
        return $this->belongsTo(Flashcard::class);
    }
}