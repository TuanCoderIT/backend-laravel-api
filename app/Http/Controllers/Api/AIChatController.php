<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIChatRequest;
use App\Services\AIChatService;

class AIChatController extends Controller
{
    private AIChatService $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    public function chat(AIChatRequest $request)
    {
        $result = $this->aiChatService->chat(
            message: $request->message,
            contextType: $request->context_type,
            contextId: $request->context_id,
            userId: $request->user()->id
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xử lý yêu cầu',
                'error' => $result['message']
            ], 500);
        }

        return response()->json([
            'message' => 'Trả lời thành công',
            'data' => [
                'response' => $result['message'],
                'metadata' => $result['metadata'] ?? [],
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}