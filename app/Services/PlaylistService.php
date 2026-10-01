<?php

namespace App\Services;

use App\Models\MediaItem;
use App\Models\Sede;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

class PlaylistService
{
    /**
     * Generate TV Playlist and calculate MD5 Version Hash
     */
    public function getPlaylist(array $params): array
    {
        $sedeId = $params['sede_id'] ?? null;
        $slug = $params['slug'] ?? null;
        $checkVersionOnly = !empty($params['check_version']) && ($params['check_version'] === '1' || $params['check_version'] === true);

        if (empty($sedeId) && empty($slug)) {
            throw ValidationException::withMessages([
                'sede' => ['Debe especificar el parámetro sede_id o slug.'],
            ]);
        }

        // Find Sede
        $query = Sede::query();
        if (!empty($sedeId)) {
            $query->where('id', (int) $sedeId);
        } else {
            $query->where('slug', trim($slug));
        }

        $sede = $query->first();

        if (!$sede) {
            return [
                'error' => 'Sede no encontrada',
                'status' => 404,
            ];
        }

        if (!$sede->is_active) {
            return [
                'error' => 'Esta sede se encuentra temporalmente inactiva en el sistema',
                'status' => 403,
            ];
        }

        // Fetch active media items
        $items = MediaItem::where('sede_id', $sede->id)
            ->where('is_active', true)
            ->orderBy('order_num', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Fetch general TV settings
        $settingsRows = Setting::all();
        $rawSettings = [];
        $settingsHash = '';
        foreach ($settingsRows as $row) {
            $rawSettings[$row->key] = $row->value;
            $settingsHash .= $row->key . ':' . $row->value . ':' . ($row->updated_at ? $row->updated_at->timestamp : '') . '_';
        }

        // Build dynamic version hash for zero-lag TV sync across all devices
        $hashString = $sede->id . '_' . ($sede->updated_at ? $sede->updated_at->timestamp : '') . '_' . $sede->name . '_' . $sede->color . '_' . ($sede->theme ?: 'default') . '_' . $items->count() . '_' . md5($settingsHash);
        foreach ($items as $item) {
            $hashString .= '_' . $item->id . ':' . ($item->updated_at ? $item->updated_at->timestamp : '') . ':' . $item->order_num . ':' . $item->duration . ':' . $item->fit_mode . ':' . ($item->is_active ? 1 : 0) . ':' . $item->title;
        }
        $versionHash = md5($hashString);

        if ($checkVersionOnly) {
            return [
                'check_version_only' => true,
                'version_hash' => $versionHash,
                'items_count' => $items->count(),
            ];
        }

        $formattedItems = $items->map(function ($item) {
            return [
                'id' => (int) $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'filename' => $item->filename,
                'url' => $item->url,
                'duration' => $item->type === 'video' ? 0 : ((int) $item->duration > 0 ? (int) $item->duration : 10),
                'fit_mode' => !empty($item->fit_mode) ? $item->fit_mode : 'contain',
                'resolution' => $item->resolution,
                'order_num' => (int) $item->order_num,
                'uploaded_by' => $item->uploaded_by_name,
            ];
        })->values();

        return [
            'check_version_only' => false,
            'version_hash' => $versionHash,
            'items_count' => $items->count(),
            'sede' => [
                'id' => $sede->id,
                'name' => $sede->name,
                'slug' => $sede->slug,
                'color' => $sede->color,
                'theme' => $sede->theme,
                'icon' => $sede->icon,
                'description' => $sede->description,
            ],
            'items' => $formattedItems,
            'playlist' => $formattedItems,
            'settings' => [
                'default_theme' => Setting::get('default_theme', 'dark-blue'),
                'tv_show_clock' => Setting::get('tv_show_clock', '1') === '1',
                'tv_show_date' => Setting::get('tv_show_date', '1') === '1',
                'tv_show_sede_title' => Setting::get('tv_show_sede_title', '1') === '1',
                'tv_show_progress_bar' => Setting::get('tv_show_progress_bar', '1') === '1',
                'tv_auto_refresh_seconds' => (int) Setting::get('tv_auto_refresh_seconds', '30'),
                'tv_transition_effect' => Setting::get('tv_transition_effect', 'fade'),
                'tv_ticker_enabled' => Setting::get('tv_ticker_enabled', '0') === '1',
                'tv_ticker_message' => Setting::get('tv_ticker_message', 'Bienvenidos a Visor TV'),
            ],
        ];
    }
}
