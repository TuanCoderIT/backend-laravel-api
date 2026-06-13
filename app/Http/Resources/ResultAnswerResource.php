<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'questionId' => $this->question_id,
            'userAnswer' => $this->user_answer,
            'correctAnswer' => $this->correct_answer,
            'isCorrect' => (bool) $this->is_correct,
            'points' => $this->points,
            'question' => $this->whenLoaded('question'),
        ];
    }
}
