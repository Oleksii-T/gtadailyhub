<?php

namespace App\Http\Requests\Api;

use App\Rules\NotRussian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditorialPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'request_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'target_post_id' => ['nullable', 'integer', Rule::exists('posts', 'id')],
            'title' => ['required', 'string', 'max:255', new NotRussian],
            'meta_title' => ['required', 'string', 'max:255'],
            'meta_description' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:200000'],
            'category_slug' => ['required', 'string', Rule::exists('categories', 'slug')],
            'status' => ['required', Rule::in(['draft', 'published', 'hidden'])],
            'author_id' => ['nullable', 'integer', Rule::exists('authors', 'id')],
            'related_ids' => ['sometimes', 'array', 'max:50'],
            'related_ids.*' => ['integer', 'distinct', Rule::exists('posts', 'id')],
            'source_url' => ['nullable', 'url:http,https', 'max:255'],
            'tags' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
