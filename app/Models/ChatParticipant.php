<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ChatParticipant.php
class ChatParticipant extends Model
{
    protected $fillable = ['thread_id', 'user_id', 'last_read_at'];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}