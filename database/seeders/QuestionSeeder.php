<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;
use App\Models\Exam;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        $exams = Exam::all();

        foreach ($exams as $exam) {
            // Random số câu hỏi 5–15
            $total = rand(5, 15);

            // Tính theo tỷ lệ
            $numMC = (int) round($total * rand(70, 80) / 100);
            $numTF = (int) round($total * rand(10, 15) / 100);
            $numSA = (int) round($total * rand(5, 10) / 100);

            // Còn lại dành cho essay
            $numEssay = max(0, $total - ($numMC + $numTF + $numSA));

            // Tạo câu hỏi từng loại
            $questions = collect()
                ->merge(Question::factory($numMC)->multipleChoice()->create())
                ->merge(Question::factory($numTF)->trueFalse()->create())
                ->merge(Question::factory($numSA)->shortAnswer()->create())
                ->merge(Question::factory($numEssay)->essay()->create());

            // Gắn vào exam (exam_question pivot)
            $order = 1;
            foreach ($questions as $q) {
                $exam->questions()->attach($q->id, ['order' => $order++]);
            }
        }
    }
}
