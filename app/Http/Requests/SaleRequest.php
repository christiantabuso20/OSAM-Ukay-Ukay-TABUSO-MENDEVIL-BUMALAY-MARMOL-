<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'payment_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the sale.',
            'items.min' => 'Add at least one product to the sale.',
            'items.*.product_id.exists' => 'One of the selected products is no longer available.',
            'items.*.quantity.min' => 'Each quantity must be at least 1.',
            'items.*.quantity.integer' => 'Each quantity must be a whole number.',
            'payment_amount.required' => 'Enter the payment amount.',
            'payment_amount.numeric' => 'The payment amount must be a number.',
            'payment_amount.min' => 'The payment amount cannot be negative.',
            'payment_amount.decimal' => 'The payment amount can have at most 2 decimal places.',
        ];
    }
}
