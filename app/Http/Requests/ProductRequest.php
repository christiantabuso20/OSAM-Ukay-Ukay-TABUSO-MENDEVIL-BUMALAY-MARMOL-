<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Basic sanitising of free-text fields.
        $this->merge([
            'name' => isset($this->name) ? trim(strip_tags($this->name)) : null,
            'description' => isset($this->description) ? trim(strip_tags($this->description)) : null,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'clothing_type' => ['required', Rule::in(Product::CLOTHING_TYPES)],
            'size' => ['required', Rule::in(Product::SIZES)],
            'condition' => ['required', Rule::in(Product::CONDITIONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        // Starting stock is only entered when the product is created.
        // Later changes go through Stock Monitoring so history stays accurate.
        if ($this->isMethod('post')) {
            $rules['initial_stock'] = ['required', 'integer', 'min:0', 'max:100000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The image must not be larger than 2 MB.',
            'image.mimes' => 'The image must be a JPG, PNG or WEBP file.',
            'unit_price.min' => 'The price cannot be negative.',
        ];
    }
}
