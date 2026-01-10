<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách user IDs
        $userIds = User::pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->warn('Không có user nào trong database. Vui lòng seed users trước.');
            return;
        }

        $transactions = [
            // Top-up transactions
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'top_up',
                'amount' => 50000,
                'description' => 'Nạp tiền qua VNPay',
                'metadata' => [
                    'payment_method' => 'vnpay',
                    'vnp_TxnRef' => 'TXN' . fake()->unique()->numberBetween(100000, 999999),
                    'vnp_TransactionNo' => fake()->unique()->numberBetween(10000000, 99999999),
                    'status' => 'completed'
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'top_up',
                'amount' => 100000,
                'description' => 'Nạp tiền qua VNPay',
                'metadata' => [
                    'payment_method' => 'vnpay',
                    'vnp_TxnRef' => 'TXN' . fake()->unique()->numberBetween(100000, 999999),
                    'vnp_TransactionNo' => fake()->unique()->numberBetween(10000000, 99999999),
                    'status' => 'completed'
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'top_up',
                'amount' => 200000,
                'description' => 'Nạp tiền qua VNPay',
                'metadata' => [
                    'payment_method' => 'vnpay',
                    'vnp_TxnRef' => 'TXN' . fake()->unique()->numberBetween(100000, 999999),
                    'vnp_TransactionNo' => fake()->unique()->numberBetween(10000000, 99999999),
                    'status' => 'completed'
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'top_up',
                'amount' => 150000,
                'description' => 'Nạp tiền qua VNPay',
                'metadata' => [
                    'payment_method' => 'vnpay',
                    'vnp_TxnRef' => 'TXN' . fake()->unique()->numberBetween(100000, 999999),
                    'vnp_TransactionNo' => fake()->unique()->numberBetween(10000000, 99999999),
                    'status' => 'completed'
                ]
            ],
            
            // Spend transactions (mua khóa học, đề thi)
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'spend',
                'amount' => -30000,
                'description' => 'Mua khóa học: Lập trình PHP cơ bản',
                'metadata' => [
                    'item_type' => 'course',
                    'item_id' => 1,
                    'item_name' => 'Lập trình PHP cơ bản',
                    'tokens_used' => 30
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'spend',
                'amount' => -15000,
                'description' => 'Mua đề thi: Quiz JavaScript',
                'metadata' => [
                    'item_type' => 'exam',
                    'item_id' => 1,
                    'item_name' => 'Quiz JavaScript',
                    'tokens_used' => 15
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'spend',
                'amount' => -45000,
                'description' => 'Mua khóa học: React Advanced',
                'metadata' => [
                    'item_type' => 'course',
                    'item_id' => 2,
                    'item_name' => 'React Advanced',
                    'tokens_used' => 45
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'spend',
                'amount' => -20000,
                'description' => 'Mua đề thi: AI Quiz từ file PDF',
                'metadata' => [
                    'item_type' => 'exam',
                    'item_id' => 3,
                    'item_name' => 'AI Quiz từ file PDF',
                    'tokens_used' => 20,
                    'ai_generated' => true
                ]
            ],
            
            // Reward transactions (thưởng hoàn thành khóa học)
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'reward',
                'amount' => 10000,
                'description' => 'Thưởng hoàn thành khóa học',
                'metadata' => [
                    'bonus_type' => 'course_completion',
                    'course_id' => 1,
                    'course_name' => 'Lập trình PHP cơ bản'
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'reward',
                'amount' => 5000,
                'description' => 'Thưởng đăng nhập hàng ngày',
                'metadata' => [
                    'bonus_type' => 'daily_login',
                    'streak_days' => 7
                ]
            ]
        ];

        foreach ($transactions as $index => $transaction) {
            Transaction::create([
                ...$transaction,
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                'updated_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))
            ]);
            
            $this->command->info("Tạo transaction " . ($index + 1) . "/10: " . $transaction['description']);
        }

        $this->command->info('✅ Đã seed 10 transactions thành công!');
    }
}