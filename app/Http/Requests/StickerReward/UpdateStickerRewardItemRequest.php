<?php

namespace App\Http\Requests\StickerReward;

use App\Models\StickerRewardItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStickerRewardItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker_reward_item.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'stock' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'status' => ['sometimes', Rule::in(StickerRewardItem::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim($this->string('name')->toString())]);
        }
    }
}
