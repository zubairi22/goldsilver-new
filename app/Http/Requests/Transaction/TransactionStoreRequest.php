<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $totalPrice = 0;

        foreach ($this->input('items', []) as $item) {
            $price = (float) ($item['selling_price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $totalPrice += $price * $quantity;
        }

        $this->merge([
            'calculated_total_price' => $totalPrice,
        ]);
    }
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
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'customer_id' => [
                Rule::requiredIf(fn () =>
                    $this->payment_method === 'deposit' || $this->paid_amount < ($this->calculated_total_price - (float) $this->discount)),
                'nullable',
                'exists:customers,id'
            ],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'redeemed_points' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Item transaksi tidak boleh kosong.',
            'items.min' => 'Minimal harus ada 1 item produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.unit_id.required' => 'Satuan wajib dipilih.',
            'items.*.quantity.min' => 'Jumlah minimal 1.',
            'items.*.purchase_price.required' => 'Harga Beli tidak boleh kosong.',
            'items.*.selling_price.required' => 'Harga Jual tidak boleh kosong.',
            'payment_method_id.exists' => 'Metode pembayaran tidak valid.',

            'customer_id.required' => 'Customer wajib dipilih.',
            'customer_id.exists' => 'Customer tidak valid.',
            'discount_amount.min' => 'Diskon tidak boleh negatif.',
            'redeemed_points.min' => 'Poin yang digunakan minimal 0.',
            'due_date.required_if' => 'Tanggal jatuh tempo wajib diisi jika belum dibayar.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo minimal hari ini.',
        ];
    }
}
