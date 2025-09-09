<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Get the menu instance from the route.
        // The name "menu" must match the wildcard in the route definition.
        $menuId = $this->route('menu')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:menus,slug,' . $this->menu->id],
            'settings' => ['nullable', 'json'],
            'tree' => ['nullable', 'array'],
            'tree.*.id' => ['required'],
            'tree.*.label' => ['required', 'string'],
            'tree.*.url' => ['nullable', 'string'],
            'tree.*.is_visible' => ['boolean'],
            // Add rules for nested children if needed
        ];
    }
}
