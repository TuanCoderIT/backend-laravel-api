<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'balance' => $wallet->balance,
        ]);
    }
}
