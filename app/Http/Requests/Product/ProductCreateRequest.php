<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],

            'units' => ['required', 'array', 'min:1'],
            'units.*.id' => ['required', 'exists:units,id'],
            'units.*.pivot.sku' => ['required', 'string'],
            'units.*.pivot.purchase_price' => ['required', 'numeric', 'min:0'],
            'units.*.pivot.selling_price' => ['required', 'numeric', 'min:0'],
            'units.*.pivot.conversion' => ['required', 'integer', 'min:1'],
        ];
    }
}
