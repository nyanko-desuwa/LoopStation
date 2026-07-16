<?php

namespace App\Http\Requests\Redemption;

use App\Models\Redemption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('redemption.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'reward_id' => ['required', 'integer', 'exists:reward_catalog,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'fulfillment_method' => ['required', Rule::in(Redemption::METHODS)],
            'recipient_name' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:150'],
            'recipient_phone' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:20'],
            'shipping_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:500'],
            'shipping_note' => ['nullable', 'string', 'max:300'],
        ];
    }
}
