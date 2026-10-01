<?php

namespace App\Http\Requests\Media;

use App\Http\Requests\BaseApiRequest;

class ReorderMediaRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|integer|exists:media_items,id',
            'orders.*.order_num' => 'required|integer',
        ];
    }
}
