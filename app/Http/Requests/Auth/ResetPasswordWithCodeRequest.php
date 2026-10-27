<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class ResetPasswordWithCodeRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6',
            'new_password_confirmation' => 'required|string|same:new_password',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El identificador de usuario es requerido.',
            'code.required' => 'El código de verificación de 6 dígitos es requerido.',
            'code.size' => 'El código de verificación debe contener exactamente 6 dígitos.',
            'new_password.required' => 'La nueva contraseña es requerida.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'new_password_confirmation.required' => 'Por favor confirme su nueva contraseña.',
            'new_password_confirmation.same' => 'Las nuevas contraseñas no coinciden.',
        ];
    }
}
