<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => isset($this->name) ? trim(strip_tags($this->name)) : null,
            'description' => isset($this->description) ? trim(strip_tags($this->description)) : null,
        ]);
    }

    /** Remember which modal to re-open after a failed save. */
    protected function failedValidation(Validator $validator): void
    {
        session()->flash('open_modal', $this->isMethod('post') ? 'create' : 'edit');
        session()->flash('edit_id', $this->route('category')?->id);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        $category = $this->route('category'); // null when creating

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'A category with this name already exists.'];
    }
}
