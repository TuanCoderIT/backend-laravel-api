<?php

namespace App\Events;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $post;

    public function __construct(PostComment $comment)
    {
        $this->comment = $comment->load(['user:id,name', 'replies.user:id,name']);
        $this->post = $comment->post->load(['user:id,name', 'group:id,name']);
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
            'comment' => $this->comment,
            'post_id' => $this->post->id,
            'comments_count' => $this->post->comments()->count(),
            'type' => 'comment_created'
        ];
    }

    public function broadcastAs()
    {
        return 'comment.created';
    }
}