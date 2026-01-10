<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'members_count',
        'owner_id',
        'visibility',
    ];

    protected $casts = [
        'members_count' => 'integer',
    ];

    /*
     * Chủ nhóm
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /*
     * Thành viên trong nhóm
     */
    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function requests()
    {
        return $this->hasMany(GroupJoinRequest::class);
    }

    /*
     * Bài viết trong nhóm
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /*
     * Scope: chỉ public group
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /*
     * Scope: group private
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /*
     * Chat thread của group
     */
    public function chatThread()
    {
        return $this->hasOne(ChatThread::class)->where('type', 'group');
    }
}
