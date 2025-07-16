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
                'passing_score' => 70,
                'max_attempts' => 3,
                'learning_objectives' => json_encode(['Understand variables', 'Write loops', 'Use functions']),
                'prerequisites' => json_encode(['Basic computer knowledge']),
                'tags' => json_encode(['javascript', 'programming']),
            ],
            [
                'title' => 'React Components & Hooks',
                'description' => 'Deep dive into React modern hooks and state.',
                'category_id' => 1,
                'difficulty' => 'Intermediate',
                'duration' => 45,
                'color' => 'green',
                'passing_score' => 70,
                'max_attempts' => 3,
                'learning_objectives' => json_encode(['Build components', 'Manage state', 'Use hooks']),
                'prerequisites' => json_encode(['JavaScript Fundamentals']),
                'tags' => json_encode(['react', 'frontend']),
            ],
            [
                'title' => 'Python for Data Science',
                'description' => 'Python basics for data analysis and ML.',
                'category_id' => 2,
                'difficulty' => 'Beginner',
                'duration' => 40,
                'color' => 'purple',
                'passing_score' => 75,
                'max_attempts' => 5,
                'learning_objectives' => json_encode(['Basic Python', 'NumPy', 'Pandas']),
                'prerequisites' => json_encode(['Basic programming']),
                'tags' => json_encode(['python', 'data-science']),
            ],
            [
                'title' => 'Database Design & SQL',
                'description' => 'Master ERD, normalization and advanced queries.',
                'category_id' => 3,
                'difficulty' => 'Intermediate',
                'duration' => 50,
                'color' => 'orange',
                'passing_score' => 70,
                'max_attempts' => 4,
                'learning_objectives' => json_encode(['ERD', 'Normalization', 'Advanced SQL']),
                'prerequisites' => json_encode(['Basic SQL']),
                'tags' => json_encode(['database', 'sql']),
            ],
            [
                'title' => 'Computer Networks Basics',
                'description' => 'Learn about TCP/IP, OSI model, and protocols.',
                'category_id' => 4,
                'difficulty' => 'Beginner',
                'duration' => 35,
                'color' => 'red',
                'passing_score' => 65,
                'max_attempts' => 3,
                'learning_objectives' => json_encode(['Understand TCP/IP', 'OSI Model', 'Common protocols']),
                'prerequisites' => json_encode(['Basic IT knowledge']),
                'tags' => json_encode(['networking', 'IT']),
            ],
        ]);
    }
}
