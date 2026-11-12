<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['remarks' => isset($this->remarks) ? trim(strip_tags($this->remarks)) : null]);
    }

    /** Re-open the movement modal after a failed save. */
    protected function failedValidation(Validator $validator): void
    {
        session()->flash('open_modal', true);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(StockMovement::allowedTypesFor($this->user()))],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'remarks' => [Rule::requiredIf(in_array($this->input('type'), [StockMovement::TYPE_OUT, StockMovement::TYPE_DAMAGED], true)), 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'You are not allowed to record this type of movement.',
            'quantity.min' => 'The quantity must be at least 1.',
            'remarks.required' => 'Please enter a reason in the remarks for Stock Out and Damaged items.',
        ];
    }
}
