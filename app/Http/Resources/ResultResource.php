<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
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
            'examId' => $this->exam_id,
            'score' => $this->score,
            'total' => $this->total,
            'percentage' => $this->percentage,
            'timeSpent' => $this->time_spent,
            'completedAt' => optional($this->completed_at)->toDateTimeString(),
            'exam' => $this->whenLoaded('exam'),
            'answers' => ResultAnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
