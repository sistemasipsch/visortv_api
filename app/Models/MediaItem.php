<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use HasFactory;

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
        'sede_id' => 'integer',
        'uploaded_by_user_id' => 'integer',
        'file_size' => 'integer',
        'duration' => 'integer',
        'order_num' => 'integer',
        'is_active' => 'boolean',
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

        // Direct clean static URL served by web server / proxy without origin or IP mismatch
        return '/storage/media/' . rawurlencode($fn);
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

        $baseName = pathinfo($fn, PATHINFO_FILENAME);
        // Candidate thumbnail file names
        $candidates = [
            'thumb_' . $baseName . '.jpg',
            'thumb_' . $fn . '.jpg',
            'thumb_' . $fn,
        ];

        foreach ($candidates as $cand) {
            $candPath = storage_path('app/public/media/' . $cand);
            if (file_exists($candPath)) {
                return '/storage/media/' . rawurlencode($cand);
            }
        }

        // For image type: Auto-generate lightweight 10KB JPEG thumbnail on the fly
        if ($this->type === 'image') {
            $origPath = storage_path('app/public/media/' . $fn);
            $targetThumbPath = storage_path('app/public/media/thumb_' . $baseName . '.jpg');
            if (file_exists($origPath) && extension_loaded('gd')) {
                try {
                    $optimizer = app(\App\Services\ImageOptimizerService::class);
                    if ($optimizer->createThumbnail($origPath, $targetThumbPath, 320, 180)) {
                        return '/storage/media/' . rawurlencode('thumb_' . $baseName . '.jpg');
                    }
                } catch (\Throwable) {
                    // Fall through to dedicated thumbnail route
                }
            }

            // Route fallback: Dedicated on-demand thumbnail generation endpoint
            return '/api/media/' . $this->id . '/thumb';
        }

        // For video without thumbnail: return empty string (never stream raw video as a thumbnail)
        return '';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return 'CDN / Enlace Web';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / (1024 * 1024), 2) . ' MB';
    }
}
