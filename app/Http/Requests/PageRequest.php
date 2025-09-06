<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'slug'       => ['nullable', 'string', 'max:255'],
            'type'       => ['required', 'string', 'max:50'],
            'status'     => ['required', Rule::in(['draft', 'review', 'published'])],
            'parent_id'  => ['nullable', 'integer', 'exists:pages,id'],

            // Accept array OR JSON string; we validate structure if array
            'sections'   => ['required'], // allow string or array; see prepareForValidation below
            'sections_arr'   => ['array'], // internal shadow (see below)
            'sections_arr.*.title'      => ['nullable', 'string', 'max:255'],
            'sections_arr.*.ui_type'    => ['required', 'string'],
            'sections_arr.*.db_type_hint' => ['nullable', 'string'],
            'sections_arr.*.order'      => ['nullable', 'integer', 'min:0'], // ignored; server sets unique
            'sections_arr.*.color'      => ['nullable', 'string', 'max:20'],

            'sections_arr.*.layout'                     => ['required', 'array'],
            'sections_arr.*.layout.columns_count'       => ['required', 'integer', 'min:1', 'max:4'],
            'sections_arr.*.layout.columns'             => ['required', 'array'],
            'sections_arr.*.layout.columns.*.index'     => ['required', 'integer', 'min:0'],
            'sections_arr.*.layout.columns.*.blocks'    => ['required', 'array'],
            'sections_arr.*.layout.columns.*.blocks.*.contentId'   => ['required', 'integer'],
            'sections_arr.*.layout.columns.*.blocks.*.contentType' => ['required', Rule::in(['post', 'media', 'block'])],
            'sections_arr.*.layout.columns.*.blocks.*.title'      => ['nullable', 'string', 'max:255'],
            'sections_arr.*.layout.columns.*.blocks.*.order'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
