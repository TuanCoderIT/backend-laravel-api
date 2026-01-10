<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WebSocketService;

class RealtimeController extends Controller
{
    /**
     * Get realtime connection info and user channels
     */
    public function getConnectionInfo(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'connection' => WebSocketService::getConnectionInfo(),
            'channels' => [
                'global' => 'posts.global',
                'groups' => WebSocketService::getUserGroupChannels($user->id),
                'chats' => WebSocketService::getUserChatChannels($user->id),
            ],
            'user_id' => $user->id
        ]);
    }

    /**
     * Test realtime connection
     */
    public function testConnection(Request $request)
    {
        $user = $request->user();
        
        // Broadcast test event
        broadcast(new \App\Events\TestRealtimeEvent($user))->toOthers();
        
        return response()->json([
            'message' => 'Test event broadcasted',
            'user' => $user->only(['id', 'name'])
        ]);
    }
}