<?php

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.exists' => 'Email Not Exists',
            'password.required' => 'A password is required.',
        ];
    }

}
