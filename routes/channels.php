<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatParticipant;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['api']]);

Broadcast::channel('chat.thread.{threadId}', function ($user, $threadId) {
    return ChatParticipant::where('thread_id', $threadId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['api']]);

// Global posts channel - public
Broadcast::channel('posts.global', function ($user) {
    return true; // Anyone can listen to global posts
}, ['guards' => ['api']]);

// Group posts channel - only group members
Broadcast::channel('group.{groupId}.posts', function ($user, $groupId) {
    return \App\Models\GroupMember::where('group_id', $groupId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['api']]);
