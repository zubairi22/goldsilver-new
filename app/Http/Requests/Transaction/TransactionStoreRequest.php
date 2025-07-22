<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['required', 'numeric', 'min:0'],

            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.unit_id.required' => 'Satuan wajib dipilih.',
            'items.*.quantity.min' => 'Jumlah minimal 1.',
            'items.*.purchase_price.required' => 'Harga Beli tidak boleh kosong.',
            'items.*.selling_price.required' => 'Harga Jual tidak boleh kosong.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ];
    }
}
