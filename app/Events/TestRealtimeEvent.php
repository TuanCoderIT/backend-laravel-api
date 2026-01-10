<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestRealtimeEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user->only(['id', 'name']);
    }

    public function broadcastOn()
    {
        return new Channel('posts.global');
    }

    public function broadcastWith()
    {
        return [
            'message' => 'Test realtime connection',
            'user' => $this->user,
            'timestamp' => now()->toISOString(),
            'type' => 'test_connection'
        ];
    }

    public function broadcastAs()
    {
        return 'test.connection';
    }
}