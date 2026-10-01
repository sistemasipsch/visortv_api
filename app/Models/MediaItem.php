<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    protected $table = 'media_items';

    protected $fillable = [
        'sede_id',
        'uploaded_by_user_id',
        'title',
        'type',
        'filename',
        'original_name',
        'mime_type',
        'file_size',
        'duration',
        'fit_mode',
        'resolution',
        'order_num',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
        'duration' => 'integer',
        'file_size' => 'integer',
        'sede_id' => 'integer',
        'uploaded_by_user_id' => 'integer',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'formatted_size',
        'uploaded_by_name',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUploadedByNameAttribute(): string
    {
        if ($this->relationLoaded('uploadedBy') && $this->uploadedBy) {
            return $this->uploadedBy->name;
        }
        if ($this->uploaded_by_user_id) {
            $user = User::find($this->uploaded_by_user_id);
            return $user ? $user->name : 'Ashly Nicole';
        }
        return 'Ashly Nicole';
    }

    public function getUrlAttribute(): string
    {
        $fn = $this->filename;
        if (empty($fn)) {
            return '';
        }

        if (str_starts_with($fn, 'http://') || str_starts_with($fn, 'https://') || str_starts_with($fn, 'data:')) {
            return $fn;
        }

        // Local media served via high-speed streaming endpoint
        return '/api/media/stream/' . rawurlencode($fn);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $fn = $this->filename;
        if (empty($fn)) {
            return '';
        }

        if (str_starts_with($fn, 'http://') || str_starts_with($fn, 'https://') || str_starts_with($fn, 'data:')) {
            return $fn;
        }

        if ($this->type === 'image') {
            return '/api/media/stream/' . rawurlencode($fn) . '?thumb=1';
        }

        return $this->url;
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return 'CDN / Enlace Web';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min((int)$pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
