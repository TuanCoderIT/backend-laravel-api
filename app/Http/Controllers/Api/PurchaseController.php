<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;
use App\Models\Transaction;
use App\Models\Wallet;

class PurchaseController extends Controller
{
    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|string',
            'target_id'   => 'required|integer',
        ]);

        $user = $request->user();
        $type = $validated['target_type'];
        $id   = $validated['target_id'];

        // Convert target_type sang FQCN để dùng morph
        $fqcn = $this->mapTargetType($type);

        // 1) Lấy giá token
        $pricing = TokenPricing::firstOrCreate(
            ['target_type' => $fqcn, 'target_id' => $id],
            ['price_token' => 0]
        );

        $price = $pricing->price_token;

        // 2) Kiểm tra đã mua chưa
        if ($this->alreadyPurchased($user->id, $fqcn, $id)) {
            return $this->respond('Bạn đã sở hữu nội dung này.', 'duplicate');
        }

        // 3) Miễn phí → chỉ lưu log
        if ($price == 0) {
            $this->logPurchase($user->id, $fqcn, $id, 0);
            return $this->respond('Nội dung miễn phí đã được thêm vào thư viện.', 'free_access');
        }

        // 4) Kiểm tra số dư
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        if ($wallet->balance < $price) {
            return response()->json(['message' => 'Không đủ token.'], 400);
        }

        // 5) Trừ token
        $wallet->decrement('balance', $price);

        // 6) Lưu Transaction
        Transaction::create([
            'user_id'     => $user->id,
            'type'        => 'spend',
            'amount'      => $price,
            'description' => "Mua {$type} #{$id}",
            'metadata'    => ['item_id' => $id, 'type' => $type],
        ]);

        // 7) Lưu PurchaseLog
        $this->logPurchase($user->id, $fqcn, $id, $price);

        return $this->respond('Mua thành công.', 'success');
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|string',
            'target_id'   => 'required|integer',
        ]);

        $fqcn = $this->mapTargetType($validated['target_type']);

        $exists = PurchaseLog::where('user_id', $request->user()->id)
            ->where('target_type', $fqcn)
            ->where('target_id', $validated['target_id'])
            ->exists();

        return response()->json(['purchased' => $exists]);
    }

    public function listMyPurchases(Request $request)
    {
        $userId = $request->user()->id;

        $logs = PurchaseLog::with('target')
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn($log) => $this->formatPurchase($log));

        return response()->json($logs);
    }

    // ---------------- HELPER FUNCTIONS ----------------

    private function mapTargetType(string $type)
    {
        return match ($type) {
            'quiz'     => \App\Models\Exam::class,
            'document' => \App\Models\Document::class,
            'course'   => \App\Models\Course::class,
            default    => abort(400, 'target_type không hợp lệ'),
        };
    }

    private function alreadyPurchased($userId, $fqcn, $id)
    {
        return PurchaseLog::where('user_id', $userId)
            ->where('target_type', $fqcn)
            ->where('target_id', $id)
            ->exists();
    }

    private function logPurchase($userId, $fqcn, $id, $price)
    {
        return PurchaseLog::create([
            'user_id'     => $userId,
            'target_type' => $fqcn,
            'target_id'   => $id,
            'token_spent' => $price,
        ]);
    }

    private function respond($message, $status)
    {
        return response()->json([
            'message' => $message,
            'status'  => $status,
        ]);
    }

    private function formatPurchase(PurchaseLog $log)
    {
        $item = $log->target;

        return [
            'id'          => $log->id,
            'type'        => class_basename($log->target_type),
            'title'       => $item->title ?? 'Unknown',
            'priceTokens' => $log->token_spent,
            'purchasedAt' => $log->created_at->toISOString(),
            'meta'        => [
                'id'     => $item->id ?? null,
                'extra'  => method_exists($item, 'toArray') ? $item->toArray() : [],
            ]
        ];
    }
}
