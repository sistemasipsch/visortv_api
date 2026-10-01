<?php

namespace App\Http\Requests\Media;

use App\Http\Requests\BaseApiRequest;

class UpdateMediaRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|nullable|string|max:200',
            'duration' => 'sometimes|nullable|integer|min:1',
            'fit_mode' => 'sometimes|nullable|string|in:contain,cover,fill',
            'order_num' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}
