<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'unlimited' => ['boolean'],
            'edit_quota' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (blank($this->input('email')) && blank($this->input('phone'))) {
                $v->errors()->add('email', __('At least an email or a phone number is required.'));
            }

            if (! $this->boolean('unlimited') && blank($this->input('edit_quota'))) {
                $v->errors()->add('edit_quota', __('Enter a quota or select the unlimited option.'));
            }
        });
    }
}
