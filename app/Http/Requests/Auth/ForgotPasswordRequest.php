<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class ForgotPasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Por favor ingrese su nombre de usuario o correo electrónico.',
        ];
    }
}
