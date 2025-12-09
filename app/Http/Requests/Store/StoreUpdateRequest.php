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
            'phone'      => 'nullable|string|max:255',
            'instagram'  => 'nullable|string|max:255',
            'address'    => 'nullable|string|max:255',

            'gold_invoice_color'   => 'required|string|max:20',
            'silver_invoice_color' => 'required|string|max:20',

            'footer_gold_wholesale'   => 'nullable|string',
            'footer_gold_retail'      => 'nullable|string',
            'footer_silver_wholesale' => 'nullable|string',
            'footer_silver_retail'    => 'nullable|string',
        ];
    }
}
