<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'content' => $this->content,
            'order' => $this->order,
            'is_free_preview' => (bool) $this->is_free_preview,
            'chapter_id' => $this->chapter_id,
            'lesson_type' => $this->lesson_type,
            'video_url' => $this->video_url,
        ];
    }
}
