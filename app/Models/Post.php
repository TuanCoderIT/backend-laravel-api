<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'attachments',
        'target_type',
        'target_id',
        'group_id',
        'is_pinned',
        'visibility',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_pinned'   => 'boolean',
        'visibility'  => 'string',
    ];

    /*
     * Người đăng bài
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Nếu bài thuộc 1 group nào đó
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /*
     * Liên kết tới Course / Document / Quiz / ... (polymorphic)
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /*
     * Comment của bài viết
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)
            ->orderBy('created_at', 'asc');
    }

    /*
     * Reaction (like, love, haha, ...) trên bài viết
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    /*
     * Scope tiện: chỉ lấy post trong group cụ thể
     */
    public function scopeInGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /*
     * Scope tiện: chỉ lấy post không thuộc group (feed chung)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('group_id');
    }

    /*
     * Get reaction summary for this post
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
     * Check if current user has reacted to this post
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

    /*
     * Check if this is a shared post
     */
    public function getIsSharedAttribute()
    {
        return $this->target_type === 'App\\Models\\Post' && $this->target_id;
    }
}
