<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TokenPricing;
use App\Models\PurchaseLog;

class QuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // App\Http\Resources\QuizResource.php
    public function toArray($request)
    {
        // đảm bảo tags luôn là mảng
        $rawTags = $this->tags ?? [];
        if (is_string($rawTags)) {
            $tags = json_decode($rawTags, true);
            if (!is_array($tags)) $tags = [];
        } elseif (is_array($rawTags)) {
            $tags = $rawTags;
        } else {
            $tags = [];
        }

        // lấy price_token (target_type = 'exams' theo DB của bạn)
        $price = TokenPricing::where('target_type', 'exams')
            ->where('target_id', $this->id)
            ->value('price_token');

        // check purchased nếu request có user (sanctum user)
        $isPurchased = false;
        if ($request->user()) {
            $isPurchased = PurchaseLog::where('user_id', $request->user()->id)
                ->where('target_type', 'exams')
                ->where('target_id', $this->id)
                ->exists();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'max_attempts' => $this->max_attempts,
            'passing_score' => $this->passing_score,
            'tags' => $tags,
            'price_token' => $price ?? 0,
            'is_purchased' => $isPurchased,
            // thêm trường khác tùy bạn cần
        ];
    }
}
