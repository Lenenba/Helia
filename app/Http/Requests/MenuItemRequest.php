<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * This is useful for cleaning or normalizing data before the rules are applied.
     */
    protected function prepareForValidation(): void
    {
        // If parent_id is an empty string, set it to null for database consistency.
        if ($this->has('parent_id') && $this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // Ensure parent_id exists in menu_items or is null.
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],

            'position' => ['nullable', 'integer', 'min:0'],

            // The label must be unique for a given menu and parent_id.
            'label' => [
                'required',
                'string',
                'max:120',
                Rule::unique('menu_items')
                    ->where('menu_id', $this->route('menu')->id)
                    ->where('parent_id', $this->input('parent_id')),
            ],

            'url' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['boolean'],
            'meta' => ['nullable', 'array'],

            // Linkable fields for polymorphic relationships.
            'linkable_type' => ['nullable', 'string', 'max:255'],
            'linkable_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get the custom validation messages for the defined rules.
     * This makes error messages more user-friendly.
     */
    public function messages(): array
    {
        return [
            'label.unique' => 'A menu item with this name already exists at this level.',
        ];
    }
}
