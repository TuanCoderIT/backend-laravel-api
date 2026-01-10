<?php

namespace Database\Seeders;

use App\Models\Exam;
use Illuminate\Database\Seeder;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;
use App\Models\Document;

class TokenSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = [1, 3, 5, 7, 9, 11];
        $priceOptions = [5, 10, 15, 20];

        /**
         * Định nghĩa các loại nội dung cần seed
         * sau này chỉ cần thêm 'course' => range(1, 5)
         */
        $targets = [
            'quiz' => Exam::inRandomOrder()->limit(20)->pluck('id')->toArray(),
            'document' => Document::inRandomOrder()->limit(20)->pluck('id')->toArray(),
            // 'course' => range(1, 5),
        ];

        // --- 1. Seed bảng giá TokenPricing ---
        foreach ($targets as $type => $ids) {
            foreach ($ids as $id) {
                TokenPricing::updateOrCreate(
                    [
                        'target_type' => $type,
                        'target_id' => $id,
                    ],
                    [
                        'price_token' => $priceOptions[array_rand($priceOptions)],
                    ]
                );
            }
        }

        // --- 2. Seed log mua ngẫu nhiên ---
        foreach ($userIds as $userId) {
            foreach ($targets as $type => $ids) {
                // Mỗi user mua ngẫu nhiên 2 item trong từng loại
                $purchasedIds = collect($ids)->random(min(2, count($ids)));

                foreach ($purchasedIds as $targetId) {
                    $price = TokenPricing::where('target_type', $type)
                        ->where('target_id', $targetId)
                        ->value('price_token') ?? 10;

                    PurchaseLog::create([
                        'user_id' => $userId,
                        'target_type' => $type,
                        'target_id' => $targetId,
                        'token_spent' => $price,
                    ]);
                }
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
