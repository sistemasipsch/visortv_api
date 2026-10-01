<?php

namespace App\Http\Requests\Media;

use App\Http\Requests\BaseApiRequest;

class BulkDeleteMediaRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ];
    }
}
