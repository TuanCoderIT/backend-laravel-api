<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WebSocketService
{
    /**
     * Broadcast to multiple channels
     */
    public static function broadcastToChannels($event, array $channels)
    {
        try {
            foreach ($channels as $channel) {
                broadcast($event)->toOthers();
            }
        } catch (\Exception $e) {
            Log::error('WebSocket broadcast failed: ' . $e->getMessage());
        }
    }

    /**
     * Get group channels for a user
     */
    public static function getUserGroupChannels($userId)
    {
        $groupIds = \App\Models\GroupMember::where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        return array_map(function($groupId) {
            return "group.{$groupId}.posts";
        }, $groupIds);
    }

    /**
     * Get chat thread channels for a user
     */
    public static function getUserChatChannels($userId)
    {
        $threadIds = \App\Models\ChatParticipant::where('user_id', $userId)
            ->pluck('thread_id')
            ->toArray();

        return array_map(function($threadId) {
            return "chat.thread.{$threadId}";
        }, $threadIds);
    }

    /**
     * Check if user can access group channel
     */
    public static function canAccessGroupChannel($userId, $groupId)
    {
        return \App\Models\GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Check if user can access chat thread
     */
    public static function canAccessChatThread($userId, $threadId)
    {
        return \App\Models\ChatParticipant::where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get realtime connection info for frontend
     */
    public static function getConnectionInfo()
    {
        return [
            'app_key' => config('broadcasting.connections.reverb.app_key'),
            'host' => config('broadcasting.connections.reverb.host', 'localhost'),
            'port' => config('broadcasting.connections.reverb.port', 8080),
            'scheme' => config('broadcasting.connections.reverb.scheme', 'ws'),
        ];
    }
}