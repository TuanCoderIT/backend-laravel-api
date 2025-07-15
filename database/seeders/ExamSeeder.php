<?php

namespace Database\Seeders;

use App\Models\Exam;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run()
    {
        Exam::insert([
            [
                'title' => 'JavaScript Fundamentals',
                'description' => 'Learn the basics of JavaScript programming.',
                'category_id' => 1,
                'difficulty' => 'Beginner',
                'duration' => 30,
                'color' => 'blue',
            ],
            [
                'title' => 'React Components & Hooks',
                'description' => 'Deep dive into React modern hooks and state.',
                'category_id' => 1,
                'difficulty' => 'Intermediate',
                'duration' => 45,
                'color' => 'green',
            ],
            [
                'title' => 'Python for Data Science',
                'description' => 'Python basics for data analysis and ML.',
                'category_id' => 2,
                'difficulty' => 'Beginner',
                'duration' => 40,
                'color' => 'purple',
            ],
            [
                'title' => 'Database Design & SQL',
                'description' => 'Master ERD, normalization and advanced queries.',
                'category_id' => 3,
                'difficulty' => 'Intermediate',
                'duration' => 50,
                'color' => 'orange',
            ],
            [
                'title' => 'Computer Networks Basics',
                'description' => 'Learn about TCP/IP, OSI model, and protocols.',
                'category_id' => 4,
                'difficulty' => 'Beginner',
                'duration' => 35,
                'color' => 'red',
            ],
        ]);
    }
}
