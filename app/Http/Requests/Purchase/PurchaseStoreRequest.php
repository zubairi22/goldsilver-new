<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'      => ['required','exists:suppliers,id'],
            'purchase_number'  => ['required','string','max:50','unique:purchases,purchase_number'],
            'status'           => ['required','in:draft,ordered,received,cancelled'],
            'ordered_at'       => ['nullable','date'],
            'note'             => ['nullable','string'],

            'items'            => ['required','array','min:1'],
            'items.*.product_id' => ['required','exists:products,id'],
            'items.*.unit_price' => ['required','integer','min:0'],
            'items.*.qty'        => ['required','integer','min:1'],
            'items.*.note'       => ['nullable','string'],
        ];
    }
}
