<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlashcardSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'visibility',
        'source_type',
        'status',
        'exam_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Chủ sở hữu bộ thẻ
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quiz nguồn (nếu tạo từ quiz)
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Danh mục
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Danh sách flashcards
    public function flashcards()
    {
        return $this->hasMany(Flashcard::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('source_type', 'ai_generated');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getCardsCountAttribute()
    {
        return $this->flashcards()->count();
    }
}
