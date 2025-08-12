<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    public function topUp(Request $request)
    {
        $request->validate(['amount' => 'required|integer|min:1']);
        $amount = $request->amount;
        $user = $request->user();

        $vnp_TmnCode = config('vnpay.vnp_TmnCode');
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');
        $vnp_Url = config('vnpay.vnp_Url');
        $vnp_ReturnUrl = config('vnpay.vnp_ReturnUrl');

        $vnp_TxnRef = uniqid();
        $vnp_OrderInfo = 'Nạp token cho user ID ' . $user->id;
        $vnp_OrderType = 'other';
        $vnp_Amount = $amount * 100 * 1000;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $request->ip();

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);

        $hashDataArr = [];
        foreach ($inputData as $key => $value) {
            $hashDataArr[] = urlencode($key) . "=" . urlencode($value);
        }
        $hashData = implode('&', $hashDataArr);

        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $query = http_build_query($inputData);
        $paymentUrl = $vnp_Url . '?' . $query . '&vnp_SecureHashType=SHA512&vnp_SecureHash=' . $vnp_SecureHash;

        // ✅ Lưu user_id theo txnRef để truy ngược khi callback
        Cache::put('vnpay_user_' . $vnp_TxnRef, $user->id, now()->addMinutes(15));

        return response()->json([
            'payment_url' => $paymentUrl
        ]);
    }

    public function vnpayReturn(Request $request)
    {
        $input = $request->all();
        $secureHash = $input['vnp_SecureHash'] ?? '';
        unset($input['vnp_SecureHash'], $input['vnp_SecureHashType']);

        ksort($input);

        // ✅ Sử dụng urlencode để match chính xác với chuỗi đã dùng trong topUp
        $hashDataArr = [];
        foreach ($input as $key => $value) {
            $hashDataArr[] = urlencode($key) . '=' . urlencode($value);
        }
        $hashData = implode('&', $hashDataArr);

        $generatedHash = hash_hmac('sha512', $hashData, config('vnpay.vnp_HashSecret'));

        if ($secureHash !== $generatedHash) {
            return redirect(config('app.frontend_url') . '/wallet/return?status=error&reason=invalid_hash');
        }

        if ($input['vnp_ResponseCode'] == '00') {
            $userId = Cache::pull('vnpay_user_' . $input['vnp_TxnRef']);

            if (!$userId) {
                return redirect(config('app.frontend_url') . '/wallet/return?status=error&reason=user_not_found');
            }

            $user = User::find($userId);
            if (!$user) {
                return redirect(config('app.frontend_url') . '/wallet/return?status=error&reason=user_not_found');
            }

            $amount = intval($input['vnp_Amount']) / 100 / 1000;

            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
            $wallet->increment('balance', $amount);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'top_up',
                'amount' => $amount,
                'description' => 'VNPay top-up: ' . $input['vnp_TxnRef'],
                'metadata' => json_encode($input),
            ]);

            return redirect(config('app.frontend_url') . '/wallet/return?status=success');
        }

        return redirect(config('app.frontend_url') . '/wallet/return?status=error');
    }


    public function handleCallback(Request $request)
    {
        $userId = $request->query('user_id');
        $amount = $request->query('amount');
        $transactionId = $request->query('transaction_id');
        $status = $request->query('status');

        if ($status !== 'success') {
            return response()->json(['message' => 'Payment failed.'], 400);
        }

        // Update ví
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        $wallet->increment('balance', $amount);

        // Ghi transaction
        Transaction::create([
            'user_id' => $userId,
            'type' => 'top_up',
            'amount' => $amount,
            'description' => 'Top-up via VNPay (fake)',
            'metadata' => json_encode([
                'transaction_id' => $transactionId,
            ]),
        ]);

        return response()->json([
            'message' => 'Top-up successful.',
            'new_balance' => $wallet->balance,
        ]);
    }
}
