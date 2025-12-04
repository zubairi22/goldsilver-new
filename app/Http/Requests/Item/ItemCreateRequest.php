<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class ItemCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'item_type_id' => ['required', 'exists:item_types,id'],
            'weight'       => ['required', 'numeric', 'min:0'],
            'price_buy'    => ['required', 'numeric', 'min:0'],
            'price_sell'   => ['required', 'numeric', 'min:0'],
            'status'       => ['required', 'string'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'         => 'Kode item wajib diisi.',
            'name.required'         => 'Nama item wajib diisi.',
            'item_type_id.required' => 'Tipe item wajib dipilih.',
            'item_type_id.exists'   => 'Tipe item tidak valid.',
            'weight.required'       => 'Berat wajib diisi.',
            'weight.numeric'        => 'Berat harus berupa angka.',
            'price_buy.required'    => 'Harga beli wajib diisi.',
            'price_sell.required'   => 'Harga jual wajib diisi.',
            'status.required'       => 'Status wajib diisi.',
        ];
    }
}
