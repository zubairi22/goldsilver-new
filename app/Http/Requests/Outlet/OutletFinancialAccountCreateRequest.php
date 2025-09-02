<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutletFinancialAccountCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required','string','max:100'],
            'code'      => ['required','string','max:100', Rule::unique('financial_accounts','code')],
            'type'      => ['required', Rule::in(['cash','bank','ewallet'])],
            'is_active' => ['sometimes','boolean'],
        ];
    }
}
