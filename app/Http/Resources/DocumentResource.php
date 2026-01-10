<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_premium' => (bool) $this->is_premium,
            'status' => $this->status,

            // File metadata
            'file_url' => $this->file_url,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            // 'thumbnail' => $this->thumbnail,
            'thumbnail' => $this->getFirstMediaUrl('thumbnails'),

            // Owner
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),

            // Category
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),

            // Token price
            'price_token' => $this->price_token ?? 0,

            // Timestamps
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
