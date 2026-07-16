<?php

namespace App\Http\Requests\StickerReward;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker_reward_item.adjust_stock') ?? false;
    }

    public function rules(): array
    {
        return [
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
