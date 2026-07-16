<?php

namespace App\Http\Requests\Sticker;

use App\Models\Sticker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStickerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'set_id' => ['required', 'integer', 'exists:sticker_sets,id'],
            'name' => ['required', 'string', 'max:150'],
            'image_url' => ['required', 'string', 'max:500'],
            'rarity' => ['sometimes', Rule::in(Sticker::RARITIES)],
            'drop_weight' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'redeem_quantity_required' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'bonus_points' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'unlocks_content_id' => ['nullable', 'integer', 'exists:educational_contents,id'],
            'status' => ['sometimes', Rule::in(Sticker::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim($this->string('name')->toString())]);
        }
    }
}
