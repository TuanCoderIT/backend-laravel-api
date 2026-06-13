<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\Result;
use App\Models\Exam;
use App\Models\Question;
use App\Models\FlashcardSet;
use App\Models\Flashcard;
use App\Models\FlashcardProgress;
use App\Models\Category;
use Database\Seeders\AchievementSeeder;

class AchievementUnlockTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed achievements
        $this->seed(AchievementSeeder::class);
        
        // Create a default category to satisfy Exam factory
        $this->category = new Category();
        $this->category->name = 'General';
        $this->category->slug = 'general';
        $this->category->save();
    }

    /** @test */
    public function it_unlocks_first_quiz_achievement()
    {
        $user = User::factory()->create(['current_streak' => 0]);
        $exam = Exam::factory()->create(['category_id' => $this->category->id]);
        $question = Question::create([
            'content' => 'Question 1',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'A',
            'points' => 1,
        ]);
        $exam->questions()->attach($question->id);

        $response = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 120,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'A', // correct
                ]
            ]
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'result',
            'unlocked_achievements'
        ]);

        // Check first_quiz unlocked
        $unlocked = $response->json('unlocked_achievements');
        $this->assertCount(2, $unlocked); // first_quiz AND perfect_score should be unlocked!
        $this->assertEquals('first_quiz', $unlocked[0]['code']);
        $this->assertEquals('perfect_score', $unlocked[1]['code']);

        // Check user_achievements table
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'first_quiz')->first()->id,
        ]);

        // Check XP reward logic
        $user->refresh();
        // Base quiz reward (50 XP from GamificationService) + first_quiz reward (100 XP) + perfect_score reward (250 XP)
        // Total should be 400 XP
        $this->assertEquals(400, $user->xp);
    }

    /** @test */
    public function it_unlocks_perfect_score_achievement()
    {
        $user = User::factory()->create(['current_streak' => 0]);
        $exam = Exam::factory()->create(['category_id' => $this->category->id]);
        $question = Question::create([
            'content' => 'Question 1',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'A',
            'points' => 1,
        ]);
        $exam->questions()->attach($question->id);

        // Submit quiz with 100% correct answers
        $response = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 120,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'A',
                ]
            ]
        ]);

        $response->assertStatus(201);
        $unlocked = $response->json('unlocked_achievements');
        $codes = collect($unlocked)->pluck('code')->all();
        $this->assertContains('perfect_score', $codes);

        // Submit another quiz with incorrect answer (no perfect score)
        $exam2 = Exam::factory()->create(['category_id' => $this->category->id]);
        $question2 = Question::create([
            'content' => 'Question 2',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'B',
            'points' => 1,
        ]);
        $exam2->questions()->attach($question2->id);

        $response2 = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam2->id,
            'time_spent' => 60,
            'answers' => [
                [
                    'question_id' => $question2->id,
                    'user_answer' => 'A', // wrong
                ]
            ]
        ]);

        $response2->assertStatus(201);
        $unlocked2 = $response2->json('unlocked_achievements');
        $this->assertEmpty($unlocked2); // no new achievements
    }

    /** @test */
    public function it_unlocks_quiz_rookie_and_master()
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create(['category_id' => $this->category->id]);
        $question = Question::create([
            'content' => 'Question 1',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'A',
            'points' => 1,
        ]);
        $exam->questions()->attach($question->id);

        // We simulate creating 4 quiz results directly first
        for ($i = 0; $i < 4; $i++) {
            Result::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'score' => 0,
                'total' => 1,
                'percentage' => 0,
                'time_spent' => 10,
                'completed_at' => now(),
            ]);
        }

        // The 5th quiz submission should trigger quiz_rookie
        $response = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 10,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'B', // wrong to avoid perfect_score
                ]
            ]
        ]);

        $response->assertStatus(201);
        $unlocked = $response->json('unlocked_achievements');
        $codes = collect($unlocked)->pluck('code')->all();
        $this->assertContains('quiz_rookie', $codes);

        // Now simulate reaching 19 quiz results
        for ($i = 0; $i < 14; $i++) {
            Result::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'score' => 0,
                'total' => 1,
                'percentage' => 0,
                'time_spent' => 10,
                'completed_at' => now(),
            ]);
        }

        // The 20th quiz submission should trigger quiz_master
        $response2 = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 10,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'B',
                ]
            ]
        ]);

        $response2->assertStatus(201);
        $unlocked2 = $response2->json('unlocked_achievements');
        $codes2 = collect($unlocked2)->pluck('code')->all();
        $this->assertContains('quiz_master', $codes2);
    }

    /** @test */
    public function it_unlocks_streak_achievements()
    {
        $user = User::factory()->create([
            'current_streak' => 2,
            'last_activity_at' => now()->subDay()->startOfDay(),
        ]);
        $exam = Exam::factory()->create(['category_id' => $this->category->id]);
        $question = Question::create([
            'content' => 'Question 1',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'A',
            'points' => 1,
        ]);
        $exam->questions()->attach($question->id);

        // Submitting quiz increments streak to 3
        $response = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 120,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'B',
                ]
            ]
        ]);

        $response->assertStatus(201);
        $unlocked = $response->json('unlocked_achievements');
        $codes = collect($unlocked)->pluck('code')->all();
        $this->assertContains('streak_3_days', $codes);

        // Check streak update in DB
        $user->refresh();
        $this->assertEquals(3, $user->current_streak);

        // Set streak to 6 and last activity to yesterday to test streak of 7
        $user->current_streak = 6;
        $user->last_activity_at = now()->subDay()->startOfDay();
        $user->save();

        // Submit another quiz to increment streak to 7
        $response2 = $this->actingAs($user)->postJson('/api/results', [
            'exam_id' => $exam->id,
            'time_spent' => 120,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'user_answer' => 'B',
                ]
            ]
        ]);

        $response2->assertStatus(201);
        $unlocked2 = $response2->json('unlocked_achievements');
        $codes2 = collect($unlocked2)->pluck('code')->all();
        $this->assertContains('streak_7_days', $codes2);
    }

    /** @test */
    public function it_unlocks_flashcard_starter_and_master()
    {
        $user = User::factory()->create();
        $flashcardSet = FlashcardSet::create([
            'user_id' => $user->id,
            'title' => 'Test Flashcard Set',
            'description' => 'Test',
            'status' => 'published',
            'visibility' => 'public',
        ]);
        $flashcard = Flashcard::create([
            'flashcard_set_id' => $flashcardSet->id,
            'front_text' => 'Front',
            'back_text' => 'Back',
        ]);

        // First review
        $response = $this->actingAs($user)->postJson("/api/flashcards/{$flashcard->id}/review", [
            'rating' => 'easy',
        ]);

        $response->assertStatus(200);
        $unlocked = $response->json('unlocked_achievements');
        $codes = collect($unlocked)->pluck('code')->all();
        $this->assertContains('flashcard_starter', $codes);

        // Simulate having reviewed 49 flashcards total
        $progress = FlashcardProgress::where('user_id', $user->id)
            ->where('flashcard_id', $flashcard->id)
            ->first();
        $progress->review_count = 49;
        $progress->save();

        // One more review triggers flashcard_master
        $response2 = $this->actingAs($user)->postJson("/api/flashcards/{$flashcard->id}/review", [
            'rating' => 'easy',
        ]);

        $response2->assertStatus(200);
        $unlocked2 = $response2->json('unlocked_achievements');
        $codes2 = collect($unlocked2)->pluck('code')->all();
        $this->assertContains('flashcard_master', $codes2);
    }

    /** @test */
    public function it_unlocks_wrong_answer_reviewer()
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create(['category_id' => $this->category->id]);
        $result = Result::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'score' => 0,
            'total' => 1,
            'percentage' => 0,
            'time_spent' => 10,
            'completed_at' => now(),
        ]);
        $question = Question::create([
            'content' => 'Q',
            'options' => ['A' => 'Option A', 'B' => 'Option B'],
            'answer' => 'A',
        ]);
        // Create a result answer record with incorrect user answer
        \App\Models\ResultAnswer::create([
            'result_id' => $result->id,
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'user_answer' => 'B', // incorrect
            'correct_answer' => 'A',
            'is_correct' => false,
            'points' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/api/results/{$result->id}/generate-wrong-answer-flashcards");

        $response->assertStatus(201);
        $unlocked = $response->json('unlocked_achievements');
        $codes = collect($unlocked)->pluck('code')->all();
        $this->assertContains('wrong_answer_reviewer', $codes);
    }
}
