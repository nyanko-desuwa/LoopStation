<?php

namespace App\Http\Requests\Reward;

use App\Models\RewardCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRewardCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reward_catalog.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'points_cost' => ['required', 'integer', 'min:1', 'max:1000000'],
            'stock' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'status' => ['sometimes', Rule::in(RewardCatalog::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim($this->string('name')->toString())]);
        }
    }
}
