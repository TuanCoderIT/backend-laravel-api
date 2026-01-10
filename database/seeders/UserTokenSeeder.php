<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class UserTokenSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = [1, 3];
        
        foreach ($userIds as $userId) {
            // Kiểm tra user có tồn tại không
            $user = User::find($userId);
            if (!$user) {
                $this->command->warn("User ID {$userId} không tồn tại. Bỏ qua.");
                continue;
            }
            
            // Tạo số token ngẫu nhiên từ 50,000 đến 500,000
            $randomBalance = fake()->numberBetween(50000, 500000);
            
            // Làm tròn đến hàng nghìn
            $balance = round($randomBalance / 1000) * 1000;
            
            // Tạo hoặc cập nhật wallet
            $wallet = Wallet::updateOrCreate(
                ['user_id' => $userId],
                ['balance' => $balance]
            );
            
            $this->command->info("✅ User ID {$userId} ({$user->name}): " . number_format($balance) . " tokens");
        }
        
        $this->command->info('🎉 Đã seed token cho user ID 1 và 3 thành công!');
    }
}