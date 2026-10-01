<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'theme' => $this->theme ?: 'dark-blue',
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at ? $this->last_login_at->toISOString() : null,
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
        ];
    }
}
