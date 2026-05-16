<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'flashcard_set_id',
        'front_text',
        'back_text',
        'explanation',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function flashcardSet()
    {
        return $this->belongsTo(FlashcardSet::class);
    }

    public function progress()
    {
        return $this->hasMany(FlashcardProgress::class);
    }
}