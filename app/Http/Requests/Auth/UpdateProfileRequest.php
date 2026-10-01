<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class UpdateProfileRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:150',
            'email' => 'sometimes|nullable|email|max:150',
            'avatar' => 'sometimes|nullable|string',
            'theme' => 'sometimes|nullable|string|max:50',
        ];
    }
}
