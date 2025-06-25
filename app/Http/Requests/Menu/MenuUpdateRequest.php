<?php

namespace App\Http\Requests\Menu;

use App\Rules\ValidRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50', Rule::unique('menus')->ignore($this->route('menu')->id)],
            'url' => [
                'required',
                'max:50',
                Rule::unique('menus')->ignore($this->route('menu')->id),
                function ($attribute, $value, $fail) {
                    if ($this->input('parent_id') !== null) {
                        (new ValidRoute())->validate($attribute, $value, $fail);
                    }
                }
            ],
            'icon' => ['required'],
            'parent_id' => ['nullable'],
            'sort' => ['required', 'integer', 'min:1'],
            'permissions' => ['required', 'array'],
        ];
    }
}
