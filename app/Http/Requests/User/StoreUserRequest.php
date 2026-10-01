<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseApiRequest;

class StoreUserRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => is_string($this->username) ? strtolower(trim($this->username)) : $this->username,
            'email' => !empty(trim((string)$this->email)) ? strtolower(trim((string)$this->email)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'nullable|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:superadmin,admin,operator',
            'avatar' => 'nullable|string',
            'theme' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }
}
