<?php

namespace App\Http\Requests\Role;

use App\Rules\ValidRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('roles')],
            'guard_name' => ['string'],
            'permissions' => ['required']
        ];
    }
}
