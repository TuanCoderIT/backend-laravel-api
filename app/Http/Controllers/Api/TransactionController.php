<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;

class TransactionController extends Controller
{
    // GET /api/me/transactions
    public function index(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    // POST /api/purchase
    public function purchase(Request $request)
    {
        $request->validate([
            'type' => 'required|in:quiz,video,document',
            'item_id' => 'required|integer'
        ]);

        $user = $request->user();
        $type = $request->type;
        $itemId = $request->item_id;

        // Tìm giá trong bảng token_pricings
        $price = TokenPricing::where('type', $type)->where('item_id', $itemId)->value('token_amount');
        if (!$price) {
            return response()->json(['message' => 'Không tìm thấy giá.'], 404);
        }

        // Kiểm tra số dư ví
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        if ($wallet->balance < $price) {
            return response()->json(['message' => 'Không đủ token.'], 400);
        }

        // Trừ token + ghi transaction
        $wallet->decrement('balance', $price);
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => $price,
            'description' => 'Mua ' . $type . ' ID ' . $itemId,
            'metadata' => [
                'category' => $type,
                'item_id' => $itemId
            ]
        ]);

        // Ghi lại purchase_log
        PurchaseLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'item_id' => $itemId,
        ]);

        return response()->json(['message' => 'Mua thành công.']);
    }
}
