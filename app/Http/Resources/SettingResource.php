<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type ?? 'string',
            'group' => $this->group ?? 'general',
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
