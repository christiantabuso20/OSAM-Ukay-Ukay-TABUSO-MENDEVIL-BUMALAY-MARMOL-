<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Create / edit a user account. Only administrators may do either. */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => isset($this->name) ? trim(strip_tags($this->name)) : null,
            'username' => isset($this->username) ? trim($this->username) : null,
            'email' => isset($this->email) ? strtolower(trim($this->email)) : null,
        ]);
    }

    /** Remember which popup to re-open after a failed save. */
    protected function failedValidation(Validator $validator): void
    {
        session()->flash('open_modal', $this->isMethod('post') ? 'create' : 'edit');
        session()->flash('edit_id', $this->route('user')?->id);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        $user = $this->route('user'); // null when creating

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
        ];

        // Passwords are only set here when creating. Existing accounts use "Reset Password".
        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'username.regex' => 'Use only letters, numbers, dots, dashes and underscores (no spaces).',
            'email.unique' => 'An account with this email already exists.',
        ];
    }
}
