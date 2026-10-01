<?php

namespace App\Services;

use App\Models\MediaItem;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    protected ImageOptimizerService $imageOptimizer;

    public function __construct(ImageOptimizerService $imageOptimizer)
    {
        $this->imageOptimizer = $imageOptimizer;
    }

    /**
     * Get media items for a specific sede
     */
    public function getBySede(int $sedeId, bool $activeOnly = false)
    {
        $query = MediaItem::with('uploadedBy')->where('sede_id', $sedeId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('order_num', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Find media item by ID
     */
    public function findById(int $id): ?MediaItem
    {
        return MediaItem::with(['sede', 'uploadedBy'])->find($id);
    }

    /**
     * Store an uploaded file (Video or Image)
     */
    public function storeUpload(UploadedFile $file, int $sedeId, array $attributes = [], ?User $actor = null): MediaItem
    {
        $sede = Sede::findOrFail($sedeId);

        $clientMime = $file->getClientMimeType() ?: $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize() ?: 0;

        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'ogg'];
        $isVideo = str_starts_with($clientMime, 'video/') || in_array($ext, $videoExtensions);
        $type = $isVideo ? 'video' : 'image';

        // Auto-detect or sanitize title
        $title = !empty($attributes['title'])
            ? trim($attributes['title'])
            : pathinfo($originalName, PATHINFO_FILENAME);

        $duration = $isVideo ? 0 : (isset($attributes['duration']) && (int) $attributes['duration'] > 0 ? (int) $attributes['duration'] : 10);
        $fitMode = in_array($attributes['fit_mode'] ?? '', ['contain', 'cover', 'fill']) ? $attributes['fit_mode'] : 'contain';

        // Unique filename
        $timestamp = time();
        $random = Str::random(8);
        $storedFilename = "media_{$timestamp}_{$random}." . ($ext ?: ($isVideo ? 'mp4' : 'jpg'));

        // Save to public storage disk
        $path = $file->storeAs('media', $storedFilename, 'public');

        $resolution = null;

        // If it's an image, optimize and auto-generate thumbnail
        if ($type === 'image') {
            try {
                $fullPath = Storage::disk('public')->path($path);
                $this->imageOptimizer->optimize($fullPath);

                if (function_exists('getimagesize')) {
                    $imgInfo = @getimagesize($fullPath);
                    if ($imgInfo) {
                        $resolution = "{$imgInfo[0]}x{$imgInfo[1]}";
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo optimizar la imagen {$storedFilename}: " . $e->getMessage());
            }
        }

        $orderNum = isset($attributes['order_num'])
            ? (int) $attributes['order_num']
            : ((MediaItem::where('sede_id', $sedeId)->max('order_num') ?: 0) + 1);

        $media = MediaItem::create([
            'sede_id' => $sedeId,
            'uploaded_by_user_id' => $actor ? $actor->id : null,
            'title' => $title,
            'type' => $type,
            'filename' => $storedFilename,
            'original_name' => $originalName,
            'mime_type' => $clientMime,
            'file_size' => $fileSize,
            'duration' => $duration,
            'fit_mode' => $fitMode,
            'resolution' => $resolution,
            'order_num' => $orderNum,
            'is_active' => isset($attributes['is_active']) ? (bool) $attributes['is_active'] : true,
        ]);

        $actorName = $actor ? $actor->name : 'Ashly Nicole';
        AuditService::log(
            'media.upload',
            "{$actorName} subió el contenido '{$title}' ({$type}) en '{$sede->name}'",
            $media,
            $sedeId,
            [
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'type' => $type,
                'duration' => $duration,
            ],
            $actor
        );

        return $media;
    }

    /**
     * Store external media URL
     */
    public function storeUrl(array $data, ?User $actor = null): MediaItem
    {
        $sedeId = (int) $data['sede_id'];
        $sede = Sede::findOrFail($sedeId);

        $url = trim($data['url']);
        $type = in_array($data['type'] ?? '', ['video', 'image']) ? $data['type'] : 'video';
        $title = !empty($data['title']) ? trim($data['title']) : 'Enlace Web (' . parse_url($url, PHP_URL_HOST) . ')';
        $duration = $type === 'video' ? 0 : (isset($data['duration']) && (int) $data['duration'] > 0 ? (int) $data['duration'] : 10);
        $fitMode = in_array($data['fit_mode'] ?? '', ['contain', 'cover', 'fill']) ? $data['fit_mode'] : 'contain';

        $orderNum = isset($data['order_num'])
            ? (int) $data['order_num']
            : ((MediaItem::where('sede_id', $sedeId)->max('order_num') ?: 0) + 1);

        $media = MediaItem::create([
            'sede_id' => $sedeId,
            'uploaded_by_user_id' => $actor ? $actor->id : null,
            'title' => $title,
            'type' => $type,
            'filename' => $url,
            'original_name' => $url,
            'mime_type' => $type === 'video' ? 'video/mp4' : 'image/jpeg',
            'file_size' => 0,
            'duration' => $duration,
            'fit_mode' => $fitMode,
            'resolution' => 'Web Stream',
            'order_num' => $orderNum,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        $actorName = $actor ? $actor->name : 'Ashly Nicole';
        AuditService::log(
            'media.add_url',
            "{$actorName} agregó un enlace web '{$title}' en '{$sede->name}'",
            $media,
            $sedeId,
            ['url' => $url, 'type' => $type],
            $actor
        );

        return $media;
    }

    /**
     * Update media item attributes
     */
    public function update(MediaItem $media, array $data, ?User $actor = null): MediaItem
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = trim($data['title']);
        }
        if (isset($data['duration'])) {
            $updateData['duration'] = $media->type === 'video' ? 0 : max(1, (int) $data['duration']);
        }
        if (isset($data['fit_mode']) && in_array($data['fit_mode'], ['contain', 'cover', 'fill'])) {
            $updateData['fit_mode'] = $data['fit_mode'];
        }
        if (isset($data['order_num'])) {
            $updateData['order_num'] = (int) $data['order_num'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        $media->update($updateData);

        $actorName = $actor ? $actor->name : 'Ashly Nicole';
        AuditService::log(
            'media.update',
            "{$actorName} actualizó la configuración de '{$media->title}'",
            $media,
            $media->sede_id,
            array_keys($updateData),
            $actor
        );

        return $media;
    }

    /**
     * Delete media item safely (Safe Unlink)
     */
    public function delete(MediaItem $media, ?User $actor = null): bool
    {
        $title = $media->title;
        $sedeId = $media->sede_id;
        $filename = $media->filename;

        // Remove physical files with non-blocking try-catch (Safe Unlink rule)
        if ($filename && !str_starts_with($filename, 'http://') && !str_starts_with($filename, 'https://')) {
            try {
                Storage::disk('public')->delete('media/' . $filename);
                Storage::disk('public')->delete('media/thumb_' . $filename);
            } catch (\Throwable $e) {
                Log::warning("No se pudo desvincular archivo físico {$filename} (puede estar en lectura activa): " . $e->getMessage());
            }
        }

        $deleted = (bool) $media->delete();

        $actorName = $actor ? $actor->name : 'Ashly Nicole';
        AuditService::log(
            'media.delete',
            "{$actorName} eliminó el contenido '{$title}'",
            null,
            $sedeId,
            ['deleted_filename' => $filename],
            $actor
        );

        return $deleted;
    }

    /**
     * Bulk delete media items
     */
    public function bulkDelete(array $ids, ?User $actor = null): int
    {
        $items = MediaItem::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($items as $item) {
            if ($this->delete($item, $actor)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Reorder media items
     */
    public function reorder(array $orders, ?User $actor = null): void
    {
        $firstItem = null;
        DB::transaction(function () use ($orders, &$firstItem) {
            foreach ($orders as $item) {
                if (isset($item['id']) && isset($item['order_num'])) {
                    MediaItem::where('id', (int) $item['id'])->update(['order_num' => (int) $item['order_num']]);
                    if (!$firstItem) {
                        $firstItem = MediaItem::find((int) $item['id']);
                    }
                }
            }
        });

        $sedeId = $firstItem ? $firstItem->sede_id : null;
        $actorName = $actor ? $actor->name : 'Ashly Nicole';

        AuditService::log(
            'media.reorder',
            "{$actorName} reordenó la secuencia de contenidos multimedia",
            null,
            $sedeId,
            ['total_items' => count($orders)],
            $actor
        );
    }
}
