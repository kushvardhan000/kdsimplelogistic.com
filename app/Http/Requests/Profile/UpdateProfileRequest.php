<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isActive();
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        } else {
            $rules['email'] = ['prohibited'];
            $rules['current_password'] = ['prohibited'];
            $rules['password'] = ['prohibited'];
        }

        if (! $user->isSuperAdmin() && $this->filled(['email', 'current_password', 'password'])) {
            $rules['email'] = ['prohibited'];
            $rules['current_password'] = ['prohibited'];
            $rules['password'] = ['prohibited'];
        }

        return $rules;
    }
}
