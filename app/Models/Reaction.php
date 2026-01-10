<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reactionable_type',
        'reactionable_id',
        'user_id',
        'reaction_type',
    ];

    /*
     * Đối tượng được reaction: Post hoặc PostComment
     */
    public function reactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
     * Người reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Scope: lọc theo loại reaction
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('reaction_type', $type);
    }
}
