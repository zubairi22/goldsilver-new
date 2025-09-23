<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required','string','max:255'],
            'phone'   => ['nullable','string','max:50'],
            'address' => ['nullable','string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'    => 'Nama supplier',
        ];
    }
}
