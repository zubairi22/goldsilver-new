<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],

            'units' => ['required', 'array', 'min:1'],
            'units.*.id' => ['required', 'exists:units,id'],
            'units.*.pivot.sku' => [
                'required',
                'string',
                'distinct',
                Rule::unique('product_unit', 'sku')->ignore($this->route('product')->id, 'product_id')
            ],
            'units.*.pivot.purchase_price' => ['required', 'numeric', 'min:0'],
            'units.*.pivot.selling_price' => ['required', 'numeric', 'min:0'],
            'units.*.pivot.conversion' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'stock.required' => 'Stok awal wajib diisi.',
            'stock.integer'  => 'Stok harus berupa angka.',
            'stock.min'      => 'Stok tidak boleh kurang dari 0.',

            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists'   => 'Kategori tidak ditemukan.',

            'units.required' => 'Minimal harus ada 1 satuan produk.',
            'units.*.id.required' => 'Satuan wajib dipilih.',
            'units.*.id.exists'   => 'Satuan tidak valid.',

            'units.*.pivot.sku.required' => 'SKU wajib diisi.',
            'units.*.pivot.sku.distinct' => 'SKU tidak boleh sama antar satuan.',
            'units.*.pivot.sku.unique'   => 'SKU ini sudah digunakan oleh produk lain.',

            'units.*.pivot.purchase_price.required' => 'Harga beli wajib diisi.',
            'units.*.pivot.purchase_price.numeric'  => 'Harga beli harus berupa angka.',
            'units.*.pivot.purchase_price.min'      => 'Harga beli minimal 0.',

            'units.*.pivot.selling_price.required' => 'Harga jual wajib diisi.',
            'units.*.pivot.selling_price.numeric'  => 'Harga jual harus berupa angka.',
            'units.*.pivot.selling_price.min'      => 'Harga jual minimal 0.',

            'units.*.pivot.conversion.required' => 'Konversi wajib diisi.',
            'units.*.pivot.conversion.integer'  => 'Konversi harus berupa bilangan bulat.',
            'units.*.pivot.conversion.min'      => 'Konversi minimal 1.',
        ];
    }
}
