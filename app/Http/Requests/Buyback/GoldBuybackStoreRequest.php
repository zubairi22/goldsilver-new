<?php

namespace App\Http\Requests\Buyback;

use Illuminate\Foundation\Http\FormRequest;

class GoldBuybackStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'exists:sales,id'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.item_id'      => ['required', 'exists:items,id'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],

            'items.*.buyback_weight' => ['required', 'numeric', 'min:0.01'],
            'items.*.buyback_price'  => ['required', 'numeric', 'min:1'],

            'items.*.image' => ['required', 'image', 'max:4096'], // 4MB
        ];
    }

    public function messages(): array
    {
        return [
            'sale_id.required' => 'Transaksi penjualan tidak ditemukan.',
            'sale_id.exists'   => 'ID penjualan tidak valid.',

            'items.required' => 'Silakan pilih setidaknya satu item untuk buyback.',
            'items.min'      => 'Minimal satu item harus dipilih.',

            'items.*.item_id.required' => 'Item tidak valid.',
            'items.*.item_id.exists'   => 'Item tidak ditemukan dalam database.',

            'items.*.sale_item_id.required' => 'Detail item tidak valid.',
            'items.*.sale_item_id.exists'   => 'Detail item tidak ditemukan.',

            'items.*.buyback_weight.required' => 'Berat buyback harus diisi.',
            'items.*.buyback_weight.numeric'  => 'Berat buyback harus berupa angka.',
            'items.*.buyback_weight.min'      => 'Berat buyback minimal 0.01 gram.',

            'items.*.buyback_price.required' => 'Harga buyback harus diisi.',
            'items.*.buyback_price.numeric'  => 'Harga buyback harus berupa angka.',
            'items.*.buyback_price.min'      => 'Harga buyback minimal Rp1.',

            'items.*.image.required' => 'Silakan unggah foto item buyback.',
            'items.*.image.image'    => 'File harus berupa gambar (jpg/png).',
            'items.*.image.max'      => 'Ukuran gambar maksimal 4MB.',
        ];
    }
}
