<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// ChatThread.php
class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'owner_id', 'group_id', 'course_id'];

    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'thread_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'thread_id')->latest();
    }

    /*
     * Group mà thread này thuộc về (nếu là group chat)
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
