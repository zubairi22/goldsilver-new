<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required','string','max:100'],
            'code'      => ['required','string','max:100', Rule::unique('payment_methods','code')],
            'is_active' => ['sometimes','boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
