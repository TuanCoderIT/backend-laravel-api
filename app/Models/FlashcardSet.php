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
        'source_type',
        'is_ai_generated',
        'exam_id',
        'color',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
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

    // Admin đã duyệt
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
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