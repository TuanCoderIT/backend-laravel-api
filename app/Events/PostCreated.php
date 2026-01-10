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

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post->load(['user:id,name', 'group:id,name']);
    }

    public function broadcastOn()
    {
        $channels = [];
        
        // Broadcast to global feed
        $channels[] = new Channel('posts.global');
        
        // If post belongs to a group, broadcast to group channel
        if ($this->post->group_id) {
            $channels[] = new Channel("group.{$this->post->group_id}.posts");
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'post' => $this->post,
            'type' => 'post_created'
        ];
    }

    public function broadcastAs()
    {
        return 'post.created';
    }
}