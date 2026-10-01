<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $userId = $this->route('id') ?: $this->route('user');

        return [
            'name' => 'sometimes|required|string|max:150',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|string|in:superadmin,admin,operator',
            'avatar' => 'sometimes|nullable|string',
            'theme' => 'sometimes|nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
