<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'unlimited' => ['boolean'],
            'edit_quota' => ['nullable', 'integer', 'min:0', 'max:100000'],
            // tenant_id فقط برای super-admin معنا دارد؛ کنترلر آن را مدیریت می‌کند.
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
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

    public function attributes(): array
    {
        return [
            'name' => __('name'),
            'email' => __('email'),
            'phone' => __('phone number'),
            'password' => __('password'),
            'edit_quota' => __('edit quota'),
        ];
    }
}
