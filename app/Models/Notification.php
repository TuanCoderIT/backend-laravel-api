<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    /*
     * Người nhận notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Đã đọc chưa
     */
    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }

    /*
     * Đánh dấu đã đọc
     */
    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /*
     * Scope: chỉ lấy chưa đọc
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
