<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'parent_id',
    ];

    protected $appends = [
        'reaction_summary',
        'user_reaction',
    ];

    /*
     * Bài viết mà comment thuộc về
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /*
     * Người comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Comment cha (nếu là reply)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /*
     * Danh sách reply của comment này
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')
            ->with('replies', 'user')
            ->orderBy('created_at', 'asc');
    }

    /*
     * Reaction trên comment
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    /*
     * Get reaction summary for this comment
     */
    public function getReactionSummaryAttribute()
    {
        return $this->reactions()
            ->selectRaw('reaction_type, COUNT(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    /*
     * Check if current user has reacted to this comment
     */
    public function getUserReactionAttribute()
    {
        if (!auth()->check()) {
            return null;
        }

        return $this->reactions()
            ->where('user_id', auth()->id())
            ->first()?->reaction_type;
    }
}
