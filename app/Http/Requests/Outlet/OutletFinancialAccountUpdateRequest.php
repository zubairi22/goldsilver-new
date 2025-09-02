<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutletFinancialAccountUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required','string','max:100'],
            'code'      => ['required','string','max:100', Rule::unique('financial_accounts','code')->ignore($this->route('financial_account')->id)],
            'type'      => ['required', Rule::in(['cash','bank','e-wallet'])],
            'is_active' => ['sometimes','boolean'],
        ];
    }
}
