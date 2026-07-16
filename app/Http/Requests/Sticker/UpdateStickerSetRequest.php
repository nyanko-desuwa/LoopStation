<?php

namespace App\Http\Requests\Sticker;

use App\Models\StickerSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStickerSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker_set.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'theme' => ['nullable', 'string', 'max:100'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(StickerSet::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim($this->string('name')->toString())]);
        }
        if ($this->filled('theme')) {
            $this->merge(['theme' => trim($this->string('theme')->toString())]);
        }
    }
}
