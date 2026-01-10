<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\ChatParticipant;
use App\Models\ChatMessage;
use Illuminate\Support\Arr;

class RandomChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            dump('Need more users to seed chat data!');
            return;
        }

        // Tạo 5–10 thread private 1-1
        for ($i = 0; $i < rand(5, 10); $i++) {
            $pair = $users->random(2);

            $thread = ChatThread::create([
                'type' => 'direct',
                'name' => null,
                'owner_id' => null,
                'group_id' => null,
                'course_id' => null
            ]);

            ChatParticipant::create([
                'thread_id' => $thread->id,
                'user_id' => $pair[0]->id
            ]);

            ChatParticipant::create([
                'thread_id' => $thread->id,
                'user_id' => $pair[1]->id
            ]);

            // Tạo 3–6 message ngẫu nhiên trong thread này
            for ($m = 0; $m < rand(3, 6); $m++) {
                ChatMessage::create([
                    'thread_id' => $thread->id,
                    'user_id' => Arr::random([$pair[0]->id, $pair[1]->id]),
                    'content' => fake()->sentence(),
                    'attachments' => null
                ]);
            }
        }

        // Tùy chọn: Tạo 1 thread group lớn mẫu
        $group = ChatThread::create([
            'type' => 'group',
            'name' => 'Nhóm học lập trình vui vẻ'
        ]);

        foreach ($users as $u) {
            ChatParticipant::create([
                'thread_id' => $group->id,
                'user_id' => $u->id
            ]);
        }

        // Tin nhắn mẫu trong group
        foreach (range(1, 10) as $index) {
            ChatMessage::create([
                'thread_id' => $group->id,
                'user_id' => $users->random()->id,
                'content' => fake()->sentence(),
                'attachments' => null
            ]);
        }
    }
}
