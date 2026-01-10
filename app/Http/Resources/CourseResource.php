<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'is_public' => (bool) $this->is_public,
            'instructor' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name ?? 'Unknown Instructor',
            ],
            'category' => $this->whenLoaded('category'),
            'chapters_count' => $this->whenCounted('chapters'),
            'created_at' => $this->created_at?->toDateTimeString() ?? 'Unknown Date',
            'price_token' => $this->tokenPricing?->price_token ?? 0,
        ];
    }
}
