<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UserTypingInThread implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public int $threadId;
    public int $userId;
    public string $userName;

    public function __construct(int $threadId, int $userId, string $userName)
    {
        $this->threadId = $threadId;
        $this->userId = $userId;
        $this->userName = $userName;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.thread.' . $this->threadId);
    }

    public function broadcastAs()
    {
        return 'user.typing';
    }
}
