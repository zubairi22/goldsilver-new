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
            'reason'              => ['nullable','string','max:1000'],
            'refund_method'       => ['required','string','max:50'],
            'external_reference'  => ['nullable','string','max:100'],
            'items'               => ['required','array','min:1'],
            'items.*.transaction_item_id' => ['required','integer','exists:transaction_items,id'],
            'items.*.quantity'    => ['required','integer','min:1'],
            'items.*.reason'      => ['nullable','string','max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.string' => 'Alasan refund harus berupa teks.',
            'reason.max'    => 'Alasan refund maksimal :max karakter.',

            'refund_method.required' => 'Metode pengembalian wajib diisi.',
            'refund_method.string'   => 'Metode pengembalian harus berupa teks.',
            'refund_method.max'      => 'Metode pengembalian tidak boleh lebih dari :max karakter.',

            'external_reference.string' => 'Referensi eksternal harus berupa teks.',
            'external_reference.max'    => 'Referensi eksternal maksimal :max karakter.',

            'items.required' => 'Daftar item refund wajib diisi.',
            'items.array'    => 'Daftar item refund harus berupa array.',
            'items.min'      => 'Pilih minimal :min item untuk direfund.',

            'items.*.transaction_item_id.required' => 'Item transaksi wajib dipilih.',
            'items.*.transaction_item_id.integer'  => 'ID item transaksi tidak valid.',
            'items.*.transaction_item_id.exists'   => 'Item transaksi yang dipilih tidak ditemukan.',

            'items.*.quantity.required' => 'Qty refund untuk setiap item wajib diisi.',
            'items.*.quantity.integer'  => 'Qty refund harus berupa bilangan bulat.',
            'items.*.quantity.min'      => 'Qty refund minimal :min.',

            'items.*.reason.string' => 'Alasan per item harus berupa teks.',
            'items.*.reason.max'    => 'Alasan per item maksimal :max karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason'                         => 'alasan refund',
            'refund_method'                  => 'metode pengembalian',
            'external_reference'             => 'referensi eksternal',
            'items'                          => 'daftar item refund',
            'items.*.transaction_item_id'    => 'item transaksi',
            'items.*.quantity'               => 'qty refund',
            'items.*.reason'                 => 'alasan item',
        ];
    }
}
