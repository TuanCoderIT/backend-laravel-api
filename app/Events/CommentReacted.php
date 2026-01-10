<?php

namespace App\Events;

use App\Models\PostComment;
use App\Models\Reaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $post;
    public $reaction;
    public $action; // 'added' or 'removed'

    public function __construct(PostComment $comment, $reaction = null, $action = 'added')
    {
        $this->comment = $comment->load('user:id,name');
        $this->post = $comment->post->load(['user:id,name', 'group:id,name']);
        $this->reaction = $reaction ? $reaction->load('user:id,name') : null;
        $this->action = $action;
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
            'comment_id' => $this->comment->id,
            'post_id' => $this->post->id,
            'reaction' => $this->reaction,
            'action' => $this->action,
            'reactions_count' => $this->comment->reactions()->count(),
            'type' => 'comment_reacted'
        ];
    }

    public function broadcastAs()
    {
        return 'comment.reacted';
    }
}