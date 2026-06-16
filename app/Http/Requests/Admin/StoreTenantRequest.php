<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tenant::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_phone' => ['nullable', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'admin_password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (blank($this->input('admin_email')) && blank($this->input('admin_phone'))) {
                $v->errors()->add('admin_email', __('The client admin requires at least an email or a phone number.'));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => __('tenant name'),
            'admin_name' => __('admin name'),
            'admin_email' => __('admin email'),
            'admin_phone' => __('admin phone'),
        ];
    }
}
