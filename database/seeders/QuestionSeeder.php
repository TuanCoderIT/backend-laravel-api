<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        Question::insert([
            [
                'content' => 'What is the correct syntax for a for loop in JavaScript?',
                'options' => json_encode([
                    'A' => 'for i = 0 to 10',
                    'B' => 'for (let i = 0; i < 10; i++)',
                    'C' => 'foreach i in range(10)',
                    'D' => 'loop i from 0 to 10'
                ]),
                'answer' => 'B',
                'explanation' => 'The standard for loop syntax in JS is for (init; condition; increment).',
            ],
            [
                'content' => 'Which hook is used for side effects in React?',
                'options' => json_encode([
                    'A' => 'useState',
                    'B' => 'useEffect',
                    'C' => 'useContext',
                    'D' => 'useRef'
                ]),
                'answer' => 'B',
                'explanation' => 'useEffect lets you run side effects in functional components.',
            ],
            [
                'content' => 'Which data type is immutable in Python?',
                'options' => json_encode([
                    'A' => 'List',
                    'B' => 'Dictionary',
                    'C' => 'Tuple',
                    'D' => 'Set'
                ]),
                'answer' => 'C',
                'explanation' => 'Tuples are immutable sequences in Python.',
            ],
            [
                'content' => 'Which SQL clause is used to filter results?',
                'options' => json_encode([
                    'A' => 'ORDER BY',
                    'B' => 'GROUP BY',
                    'C' => 'HAVING',
                    'D' => 'WHERE'
                ]),
                'answer' => 'D',
                'explanation' => 'The WHERE clause filters rows returned by the SELECT statement.',
            ],
            [
                'content' => 'Which protocol is used for secure HTTP connections?',
                'options' => json_encode([
                    'A' => 'FTP',
                    'B' => 'SMTP',
                    'C' => 'TLS/SSL',
                    'D' => 'UDP'
                ]),
                'answer' => 'C',
                'explanation' => 'TLS/SSL is used to secure HTTP (HTTPS) connections.',
            ],
        ]);
    }
}
