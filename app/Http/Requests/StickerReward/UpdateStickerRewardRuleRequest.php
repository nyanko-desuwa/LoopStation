<?php

namespace App\Http\Requests\StickerReward;

use App\Models\StickerRewardRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStickerRewardRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker_reward_rule.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'status' => ['sometimes', Rule::in(StickerRewardRule::STATUSES)],
        ];
    }
}
