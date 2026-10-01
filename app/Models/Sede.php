<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    protected $table = 'sedes';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'color',
        'theme',
        'icon',
        'order_num',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
        'created_by_user_id' => 'integer',
    ];

    protected $appends = [
        'total_media',
        'active_media',
        'total_videos',
        'total_images',
    ];

    public function mediaItems(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'sede_id')->orderBy('order_num', 'asc');
    }

    public function activeMediaItems(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'sede_id')
            ->where('is_active', true)
            ->orderBy('order_num', 'asc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'sede_id')->latest('created_at');
    }

    public function getTotalMediaAttribute(): int
    {
        if (array_key_exists('total_media', $this->attributes)) {
            return (int) $this->attributes['total_media'];
        }
        if ($this->relationLoaded('mediaItems')) {
            return $this->mediaItems->count();
        }
        return $this->mediaItems()->count();
    }

    public function getActiveMediaAttribute(): int
    {
        if (array_key_exists('active_media', $this->attributes)) {
            return (int) $this->attributes['active_media'];
        }
        if ($this->relationLoaded('activeMediaItems')) {
            return $this->activeMediaItems->count();
        }
        return $this->activeMediaItems()->count();
    }

    public function getTotalVideosAttribute(): int
    {
        if (array_key_exists('total_videos', $this->attributes)) {
            return (int) $this->attributes['total_videos'];
        }
        if ($this->relationLoaded('mediaItems')) {
            return $this->mediaItems->where('type', 'video')->count();
        }
        return $this->mediaItems()->where('type', 'video')->count();
    }

    public function getTotalImagesAttribute(): int
    {
        if (array_key_exists('total_images', $this->attributes)) {
            return (int) $this->attributes['total_images'];
        }
        if ($this->relationLoaded('mediaItems')) {
            return $this->mediaItems->where('type', 'image')->count();
        }
        return $this->mediaItems()->where('type', 'image')->count();
    }
}
