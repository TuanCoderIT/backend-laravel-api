<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AIChatService
{
    private AIRouterService $aiRouter;
    private AIActionHandlerService $actionHandler;
    private AIFormatterService $aiFormatter;

    public function __construct(
        AIRouterService $aiRouter,
        AIActionHandlerService $actionHandler,
        AIFormatterService $aiFormatter
    ) {
        $this->aiRouter = $aiRouter;
        $this->actionHandler = $actionHandler;
        $this->aiFormatter = $aiFormatter;
    }

    public function chat(string $message, ?string $contextType = null, ?int $contextId = null, int $userId = null): array
    {
        try {
            // Step 1: AI Router phân tích intent
            $routerResult = $this->aiRouter->analyzeIntent($message, $contextType, $contextId);
            
            if (!$routerResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Không thể phân tích câu hỏi của bạn. Vui lòng thử lại.',
                    'debug' => $routerResult
                ];
            }

            $intent = $routerResult['intent'];
            $action = $intent['action'];
            $params = $intent['params'] ?? [];

            // Step 2: Backend xử lý action và query DB
            $actionResult = $this->actionHandler->handleAction($action, $params, $userId ?? auth()->id());
            
            if (!$actionResult['success']) {
                return [
                    'success' => false,
                    'message' => $actionResult['message'],
                    'debug' => ['action' => $action, 'params' => $params]
                ];
            }

            // Step 3: AI Formatter tạo response tự nhiên
            $formattedResponse = $this->aiFormatter->formatResponse(
                $action, 
                $actionResult['data'] ?? [], 
                $message
            );

            return [
                'success' => true,
                'message' => $formattedResponse,
                'metadata' => [
                    'action' => $action,
                    'params' => $params,
                    'data_found' => !empty($actionResult['data'])
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage(), [
                'message' => $message,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => 'Xin lỗi, tôi không thể trả lời câu hỏi này lúc này. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ];
        }
    }
}