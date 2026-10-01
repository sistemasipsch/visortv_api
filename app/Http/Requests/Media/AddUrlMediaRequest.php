<?php

namespace App\Http\Requests\Media;

use App\Http\Requests\BaseApiRequest;

class AddUrlMediaRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:sedes,id',
            'url' => 'required|url|max:1000',
            'title' => 'nullable|string|max:200',
            'type' => 'nullable|string|in:video,image',
            'duration' => 'nullable|integer|min:0',
            'fit_mode' => 'nullable|string|in:contain,cover,fill',
            'order_num' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }
}
