<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        // Author được sửa bài của mình; content.update cho staff/manager.
        $content = $this->route('content');
        if ($content && (int) $content->author_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('content.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'content' => ['sometimes', 'string'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'timer_seconds' => ['sometimes', 'integer', 'min:1', 'max:86400'],
            'points_reward' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge(['title' => trim($this->string('title')->toString())]);
        }
    }
}
