<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardSetResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn() => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'categoryId' => $this->category_id,
            'sourceType' => $this->source_type,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'cardCount' => $this->cardCount(),
            'masteredCount' => $this->masteredCount(),
            'cards' => FlashcardResource::collection($this->whenLoaded('flashcards')),
            'user' => $this->whenLoaded('user', fn() => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    private function cardCount(): int
    {
        if (isset($this->flashcards_count)) {
            return (int) $this->flashcards_count;
        }

        if ($this->relationLoaded('flashcards')) {
            return $this->flashcards->count();
        }

        return 0;
    }

    private function masteredCount(): int
    {
        if (isset($this->mastered_count)) {
            return (int) $this->mastered_count;
        }

        if ($this->relationLoaded('flashcards')) {
            return $this->flashcards->filter(function ($flashcard) {
                return $flashcard->relationLoaded('progress')
                    && $flashcard->progress->contains('status', 'mastered');
            })->count();
        }

        return 0;
    }
}
