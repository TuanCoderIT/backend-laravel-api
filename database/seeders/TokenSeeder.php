<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;

class TokenSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = [21, 22, 23, 26, 27, 28, 30, 31];
        foreach (range(1, 3) as $quizId) {
            TokenPricing::updateOrCreate([
                'target_type' => 'quiz',
                'target_id' => $quizId,
            ], [
                'price_token' => [5, 10, 15][array_rand([5, 10, 15])],
            ]);
        }

        // Gán mỗi user đã mua ngẫu nhiên 2 quiz
        foreach ($userIds as $userId) {
            $purchasedQuizIds = collect(range(1, 3))->random(2);

            foreach ($purchasedQuizIds as $quizId) {
                $price = TokenPricing::where('target_type', 'quiz')
                    ->where('target_id', $quizId)
                    ->value('price_token') ?? 10;

                PurchaseLog::create([
                    'user_id' => $userId,
                    'target_type' => 'quiz',
                    'target_id' => $quizId,
                    'token_spent' => $price,
                ]);
            }
        }
        // foreach ($userIds as $userId) {
        //     // Tạo hoặc cập nhật ví
        //     Wallet::updateOrCreate(
        //         ['user_id' => $userId],
        //         ['balance' => rand(10, 200)]
        //     );

        //     // Tạo 3 giao dịch token
        //     Transaction::factory(3)->create([
        //         'user_id' => $userId,
        //     ]);
        // }
    }
}
