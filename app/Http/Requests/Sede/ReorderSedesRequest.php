<?php

namespace App\Http\Requests\Sede;

use App\Http\Requests\BaseApiRequest;

class ReorderSedesRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|integer|exists:sedes,id',
            'orders.*.order_num' => 'required|integer',
        ];
    }
}
