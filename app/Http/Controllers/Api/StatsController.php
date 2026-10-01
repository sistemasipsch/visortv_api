<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaItem;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Dashboard statistics for admin overview
     */
    public function index(): JsonResponse
    {
        // Sedes stats
        $totalSedes = Sede::count();
        $activeSedes = Sede::where('is_active', true)->count();

        // Media stats
        $totalMedia = MediaItem::count();
        $totalVideos = MediaItem::where('type', 'video')->count();
        $totalImages = MediaItem::where('type', 'image')->count();
        $activeMedia = MediaItem::where('is_active', true)->count();
        $totalStorageBytes = (int) MediaItem::sum('file_size');

        // Users stats
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();

        // Sedes with counts
        $sedes = Sede::orderBy('order_num', 'asc')->orderBy('id', 'asc')->get()->map(function ($sede) {
            $media = MediaItem::where('sede_id', $sede->id)->get();
            return [
                'id' => $sede->id,
                'name' => $sede->name,
                'slug' => $sede->slug,
                'color' => $sede->color,
                'icon' => $sede->icon,
                'is_active' => (bool) $sede->is_active,
                'media_count' => $media->count(),
                'video_count' => $media->where('type', 'video')->count(),
                'image_count' => $media->where('type', 'image')->count(),
                'active_media_count' => $media->where('is_active', true)->count(),
            ];
        });

        // Recent media uploads
        $recentMedia = MediaItem::with(['sede', 'uploadedBy'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'filename' => $item->filename,
                    'url' => $item->url,
                    'file_size' => $item->file_size,
                    'formatted_size' => $item->formatted_size,
                    'uploaded_by' => $item->uploaded_by_name,
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                    'created_at_human' => $item->created_at ? $item->created_at->diffForHumans() : '',
                    'sede_name' => $item->sede ? $item->sede->name : 'Sede',
                    'sede_color' => $item->sede ? $item->sede->color : '#2563eb',
                ];
            });

        // Recent audit events (Only accessible to superadmin)
        $user = auth('sanctum')->user() ?: request()->user();
        $isSuperAdmin = $user && $user->role === 'superadmin';

        $recentAudits = [];
        if ($isSuperAdmin) {
            $recentAudits = AuditLog::latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_name' => $log->user_name,
                        'sede_name' => $log->sede_name,
                        'action' => $log->action,
                        'description' => $log->description,
                        'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : '',
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'total_sedes' => $totalSedes,
                'active_sedes' => $activeSedes,
                'total_media' => $totalMedia,
                'total_videos' => $totalVideos,
                'total_images' => $totalImages,
                'active_media' => $activeMedia,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_storage_bytes' => $totalStorageBytes,
                'formatted_storage' => $this->formatBytes($totalStorageBytes),
            ],
            'sedes' => $sedes,
            'recent_media' => $recentMedia,
            'recent_audits' => $recentAudits,
            'system' => [
                'app_name' => 'Visor TV Enterprise',
                'author' => 'Ashly Nicole',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min((int)$pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
