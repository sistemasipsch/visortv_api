<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileSize = (int) $this->file_size;
        $sizeBadge = [
            'label' => $this->formatted_size,
            'tone' => 'slate',
            'is_heavy' => false,
            'is_optimal' => false,
        ];

        if ($this->type === 'video') {
            if ($fileSize > 50 * 1024 * 1024) {
                $sizeBadge['label'] = $this->formatted_size . ' ⚠️ Pesado';
                $sizeBadge['tone'] = 'amber';
                $sizeBadge['is_heavy'] = true;
            } elseif ($fileSize > 0 && $fileSize <= 25 * 1024 * 1024) {
                $sizeBadge['label'] = $this->formatted_size . ' (Óptimo)';
                $sizeBadge['tone'] = 'emerald';
                $sizeBadge['is_optimal'] = true;
            }
        }

        return [
            'id' => (int) $this->id,
            'sede_id' => (int) $this->sede_id,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'uploaded_by_name' => $this->uploaded_by_name,
            'title' => $this->title,
            'type' => $this->type,
            'type_label' => $this->type === 'video' ? 'Video' : 'Imagen',
            'filename' => $this->filename,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $fileSize,
            'formatted_size' => $this->formatted_size,
            'size_badge' => $sizeBadge,
            'duration' => (int) $this->duration,
            'duration_label' => $this->type === 'video' ? 'Duración completa' : (($this->duration ?: 10) . 's en pantalla'),
            'fit_mode' => $this->fit_mode ?: 'contain',
            'fit_mode_label' => $this->fit_mode === 'cover' ? 'Llenar pantalla' : 'Ajustar a pantalla',
            'resolution' => $this->resolution,
            'order_num' => (int) $this->order_num,
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Activo' : 'Oculto',
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'created_at_human' => $this->created_at ? $this->created_at->diffForHumans() : '',
        ];
    }
}
