<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EducationalContent */
class EducationalContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author_id' => $this->author_id,
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null),
            'approved_by_id' => $this->approved_by_id,
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'thumbnail_url' => $this->thumbnail_url,
            'status' => $this->status,
            'timer_seconds' => $this->timer_seconds,
            'points_reward' => $this->points_reward,
            'sticker_set_id' => $this->sticker_set_id,
            'sticker_set' => $this->whenLoaded('stickerSet', fn () => $this->stickerSet ? [
                'id' => $this->stickerSet->id,
                'name' => $this->stickerSet->name,
                'status' => $this->stickerSet->status,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
