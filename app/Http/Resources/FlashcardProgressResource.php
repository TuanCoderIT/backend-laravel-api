<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'correctCount' => (int) $this->correct_count,
            'reviewCount' => (int) $this->review_count,
            'nextReviewAt' => optional($this->next_review_at)->toDateTimeString(),
        ];
    }
}
