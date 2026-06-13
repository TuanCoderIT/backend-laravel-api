<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardResource extends JsonResource
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
            'term' => $this->front_text,
            'definition' => $this->back_text,
            'explanation' => $this->explanation,
            'progress' => $this->whenLoaded('progress', function () {
                $progress = $this->progress->first();

                return $progress ? new FlashcardProgressResource($progress) : null;
            }),
        ];
    }
}
