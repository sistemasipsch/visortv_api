<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SedeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'color' => $this->color ?: '#2563eb',
            'theme' => $this->theme,
            'icon' => $this->icon ?: 'Building2',
            'order_num' => (int) $this->order_num,
            'is_active' => (bool) $this->is_active,
            'total_media' => (int) $this->total_media,
            'active_media' => (int) $this->active_media,
            'total_videos' => (int) $this->total_videos,
            'total_images' => (int) $this->total_images,
            'created_by' => $this->createdBy ? $this->createdBy->name : 'Ashly Nicole',
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
