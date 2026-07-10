<?php

namespace App\Rules;

use App\Models\User;
use App\Support\EmailBox;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistingCanonicalEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $canonical = EmailBox::normalize($value);

        if ($canonical === '') {
            return;
        }

        $exists = User::query()
            ->where('email_canonical', $canonical)
            ->exists();

        if (! $exists) {
            $fail(__('auth.email_not_found'));
        }
    }
}
