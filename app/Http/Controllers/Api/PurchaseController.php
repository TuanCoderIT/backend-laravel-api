<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;
use App\Models\Exam;

class PurchaseController extends Controller
{
    public function purchase(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string',   // ví dụ: 'quiz', 'material', 'video'
            'target_id' => 'required|integer'
        ]);

        $user = $request->user();
        $type = $request->target_type;
        $id = $request->target_id;

        // 1. Lấy giá token
        $price = TokenPricing::where('target_type', $type)
            ->where('target_id', $id)
            ->value('price_token');

        if (!$price) {
            return response()->json(['message' => 'Không tìm thấy giá.'], 404);
        }

        // 2. Kiểm tra số dư ví
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        if ($wallet->balance < $price) {
            return response()->json(['message' => 'Không đủ token.'], 400);
        }

        // 3. Trừ token
        $wallet->decrement('balance', $price);

        // 4. Tạo transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => $price,
            'description' => 'Mua ' . $type . ' ID ' . $id,
            'metadata' => [
                'category' => match ($type) {
                    'quiz' => 'course',
                    'video', 'document', 'material' => $type,
                    default => 'other',
                },
                'item_id' => $id,
            ],
        ]);

        // 5. Ghi purchase_log
        PurchaseLog::create([
            'user_id' => $user->id,
            'target_type' => $type,
            'target_id' => $id,
            'token_spent' => $price,
        ]);

        return response()->json(['message' => 'Mua thành công.']);
    }

    public function check(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string',
            'target_id' => 'required|integer',
        ]);

        $user = $request->user();

        $purchased = PurchaseLog::where('user_id', $user->id)
            ->where('target_type', $request->target_type)
            ->where('target_id', $request->target_id)
            ->exists();

        return response()->json(['purchased' => $purchased]);
    }

    public function listMyPurchases(Request $request)
    {
        $userId = $request->user()->id;

        // Lấy tất cả purchases của user
        $purchases = PurchaseLog::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function ($purchase) {
                $type = $purchase->target_type;
                $id = $purchase->target_id;

                // Mặc định dữ liệu cơ bản
                $itemData = [
                    'id'          => $purchase->id,
                    'type'        => $type,
                    'title'       => 'Unknown Item',
                    'description' => '',
                    'thumbnailUrl' => null,
                    'priceTokens' => $purchase->token_spent ?? null,
                    'purchasedAt' => $purchase->created_at->toISOString(),
                    'meta'        => []
                ];

                // Lấy dữ liệu tùy loại
                switch ($type) {
                    case 'quiz':
                        $exam = Exam::find($id);
                        if ($exam) {
                            $itemData['title']       = $exam->title;
                            $itemData['description'] = $exam->description;
                            $itemData['thumbnailUrl'] = null; // Có thể thêm cột thumbnail sau
                            $itemData['meta'] = [
                                'examId'         => $exam->id,
                                'difficulty'     => $exam->difficulty ?? 'Beginner',
                                'durationMinutes' => $exam->duration ?? 0,
                                'questionsCount' => $exam->questions()->count(),
                                'color'          => 'blue'
                            ];
                        }
                        break;

                    case 'course':
                        // TODO: join bảng courses
                        break;

                    case 'video':
                        // TODO: join bảng videos
                        break;

                    case 'document':
                        // TODO: join bảng documents
                        break;
                }

                return $itemData;
            });

        return response()->json($purchases);
    }
}
