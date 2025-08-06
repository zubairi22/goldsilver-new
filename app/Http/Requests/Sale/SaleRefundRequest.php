<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class SaleRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:' . ($this->route('transaction')?->total_price ?? 0),
            ],
            'refund_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_amount.required' => 'Jumlah refund wajib diisi.',
            'refund_amount.max' => 'Jumlah refund tidak boleh melebihi total transaksi.',
        ];
    }
}
