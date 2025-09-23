<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'      => ['required','exists:suppliers,id'],
            'status'           => ['required','in:draft,ordered,received,cancelled'],
            'ordered_at'       => ['nullable','date'],
            'note'             => ['nullable','string'],

            'items'            => ['required','array','min:1'],
            'items.*.id'         => ['nullable','exists:purchase_items,id'],
            'items.*.product_id' => ['required','exists:products,id'],
            'items.*.unit_price' => ['required','integer','min:0'],
            'items.*.qty'        => ['required','integer','min:1'],
            'items.*.note'       => ['nullable','string'],
        ];
    }
}
