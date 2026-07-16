<?php

namespace App\Http\Requests\StickerReward;

use App\Models\StickerRedemption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStickerRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sticker.redeem') ?? false;
    }

    public function rules(): array
    {
        return [
            'sticker_id' => ['required', 'integer', 'exists:stickers,id'],
            'fulfillment_method' => ['required', Rule::in(StickerRedemption::METHODS)],
            'facility_id' => ['required_if:fulfillment_method,pickup', 'nullable', 'integer', 'exists:facilities,id'],
            'recipient_name' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:150'],
            'recipient_phone' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:20'],
            'shipping_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:500'],
            'shipping_note' => ['nullable', 'string', 'max:300'],
        ];
    }
}
