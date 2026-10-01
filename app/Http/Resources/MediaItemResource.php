<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'sede_id' => (int) $this->sede_id,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'uploaded_by_name' => $this->uploaded_by_name,
            'title' => $this->title,
            'type' => $this->type,
            'filename' => $this->filename,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => (int) $this->file_size,
            'formatted_size' => $this->formatted_size,
            'duration' => (int) $this->duration,
            'fit_mode' => $this->fit_mode ?: 'contain',
            'resolution' => $this->resolution,
            'order_num' => (int) $this->order_num,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'created_at_human' => $this->created_at ? $this->created_at->diffForHumans() : '',
        ];
    }
}
