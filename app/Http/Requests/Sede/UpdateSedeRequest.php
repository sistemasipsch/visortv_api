<?php

namespace App\Http\Requests\Sede;

use App\Http\Requests\BaseApiRequest;

class UpdateSedeRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:150',
            'slug' => 'sometimes|nullable|string|max:150',
            'description' => 'sometimes|nullable|string|max:1000',
            'address' => 'sometimes|nullable|string|max:255',
            'color' => 'sometimes|nullable|string|max:30',
            'theme' => 'sometimes|nullable|string|max:50',
            'icon' => 'sometimes|nullable|string|max:2048',
            'order_num' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}
