<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $originalPost;
    public $sharedPost;
    public $sharedBy;

    public function __construct(Post $originalPost, Post $sharedPost)
    {
        $this->originalPost = $originalPost->load(['user:id,name', 'group:id,name']);
        $this->sharedPost = $sharedPost->load(['user:id,name', 'group:id,name']);
        $this->sharedBy = $sharedPost->user;
    }

    public function broadcastOn()
    {
        $channels = [];
        
        // Broadcast to global feed
        $channels[] = new Channel('posts.global');
        
        // If shared post belongs to a group, broadcast to group channel
        if ($this->sharedPost->group_id) {
            $channels[] = new Channel("group.{$this->sharedPost->group_id}.posts");
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'original_post' => $this->originalPost,
            'shared_post' => $this->sharedPost,
            'shared_by' => $this->sharedBy,
            'type' => 'post_shared'
        ];
    }

    public function broadcastAs()
    {
        return 'post.shared';
    }
}