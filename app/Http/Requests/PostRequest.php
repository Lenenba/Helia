<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adapte à ta logique auth
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:posts,title'],
            'excerpt' => ['nullable', 'string', 'max:5000'],

            'content' => ['required', 'string'],

            'cover_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'cover_image_file' => ['nullable', 'file', 'image', 'max:10240'], // 10MB

            'image_position' => ['required', Rule::in(['left', 'right'])],
            // 'show_title' => ['required', 'boolean'],

            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],

            'selected_tag_ids' => ['array'],
            'selected_tag_ids.*' => ['integer', 'exists:tags,id'],
            'new_tags' => ['array'],
            'new_tags.*' => ['string', 'max:50'],
        ];
    }
}
