<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ChatMessage.php
class ChatMessage extends Model
{
    protected $fillable = ['thread_id', 'user_id', 'content', 'attachments'];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->morphMany(\App\Models\Reaction::class, 'reactionable');
    }

}
