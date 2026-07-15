<?php

namespace App\Http\Requests\Reward;

use App\Models\RewardCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reward_catalog.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'points_cost' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['sometimes', 'required', Rule::in(RewardCatalog::STATUSES)],
        ];
    }
}
