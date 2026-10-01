<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name ?: ($this->user ? $this->user->name : 'Sistema'),
            'user_avatar' => $this->user ? $this->user->avatar : null,
            'sede_id' => $this->sede_id,
            'sede_name' => $this->sede_name ?: ($this->sede ? $this->sede->name : null),
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'description' => $this->description,
            'details' => $this->details,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'created_at_human' => $this->created_at ? $this->created_at->diffForHumans() : '',
            'created_at_formatted' => $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : '',
        ];
    }
}
