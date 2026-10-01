<?php

namespace App\Http\Requests\Sede;

use App\Http\Requests\BaseApiRequest;

class StoreSedeRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
            'icon' => 'nullable|string|max:2048',
            'order_num' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'theme' => 'nullable|string|max:50',
        ];
    }
}
