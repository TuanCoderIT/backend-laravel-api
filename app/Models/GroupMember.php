<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
    ];

    /*
     * Group mà user này tham gia
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /*
     * User là thành viên của group
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Helper: kiểm tra có phải admin group không
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }
}
