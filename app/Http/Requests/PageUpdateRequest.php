<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class PageUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:page,post,custom'],
            'status' => ['required', 'in:draft,review,published'],
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'sections' => ['array'],
            'sections.*.title' => ['required', 'string'],
            'sections.*.ui_type' => ['nullable', 'string'],
            'sections.*.db_type_hint' => ['nullable', 'in:one_column,two_columns,hero,gallery'],
            'sections.*.order' => ['required', 'integer'],
            'sections.*.layout' => ['required', 'array'],
            'sections.*.layout.columns_count' => ['required', 'integer', 'min:1', 'max:4'],
            'sections.*.layout.columns' => ['required', 'array'],
            'sections.*.layout.columns.*.id' => ['nullable', 'string'],
            'sections.*.layout.columns.*.index' => ['required', 'integer'],
            'sections.*.layout.columns.*.blocks' => ['array'],
            'sections.*.layout.columns.*.blocks.*.id' => ['required', 'string'],
            'sections.*.layout.columns.*.blocks.*.contentId' => ['required', 'integer'],
            'sections.*.layout.columns.*.blocks.*.contentType' => ['required', 'in:post,media,block'],
            'sections.*.layout.columns.*.blocks.*.title' => ['nullable', 'string'],
            'sections.*.layout.columns.*.blocks.*.order' => ['required', 'integer'],
        ];
    }
}
