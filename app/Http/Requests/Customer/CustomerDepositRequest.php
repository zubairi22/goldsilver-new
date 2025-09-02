<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1000'],
            'description' => ['nullable', 'string'],
            'financial_account_id'=> ['required', 'exists:financial_accounts,id'],
            'external_reference'  => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'              => 'Jumlah deposit wajib diisi.',
            'amount.numeric'               => 'Jumlah deposit harus berupa angka.',
            'amount.min'                   => 'Minimal jumlah deposit adalah Rp 1.000.',

            'description.string'           => 'Deskripsi harus berupa teks.',
            'description.max'              => 'Deskripsi maksimal :max karakter.',

            'external_reference.string'    => 'Referensi eksternal harus berupa teks.',
            'external_reference.max'       => 'Referensi eksternal maksimal :max karakter.',

            'financial_account_id.required'=> 'Akun keuangan wajib dipilih.',
            'financial_account_id.exists'  => 'Akun keuangan yang dipilih tidak valid.',
        ];
    }
}

