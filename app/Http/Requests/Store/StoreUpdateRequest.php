<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',

            'invoice_color' => 'required|string|max:20',

            'header' => 'nullable|string',

            'footer_wholesale' => 'nullable|string',
            'footer_retail' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ];
    }
}