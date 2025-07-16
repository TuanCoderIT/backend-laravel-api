<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Question;

class ExamQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $exam1 = Exam::first();
        $questions = Question::all();
        $exam1->questions()->attach($questions->pluck('id'));
    }
}

