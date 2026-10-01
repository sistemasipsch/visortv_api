<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    protected PlaylistService $playlistService;

    public function __construct(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
    }

    /**
     * Public high-performance playlist for Smart TVs and Visor displays
     */
    public function index(Request $request): JsonResponse
    {
        $sedeId = $request->input('sede_id', $request->query('sede_id'));
        $slug = $request->input('slug', $request->query('slug'));
        $checkVersionOnly = $request->boolean('check_version') || $request->input('check_version') === '1' || $request->query('check_version') === '1';

        if (empty($sedeId) && empty($slug)) {
            return response()->json([
                'success' => false,
                'error' => 'Debe especificar el parámetro sede_id o slug',
            ], 400);
        }

        $result = $this->playlistService->getPlaylist([
            'sede_id' => $sedeId,
            'slug' => $slug,
            'check_version' => $checkVersionOnly,
        ]);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $result['status'] ?? 404);
        }

        if (!empty($result['check_version_only'])) {
            return response()->json([
                'success' => true,
                'version_hash' => $result['version_hash'],
                'items_count' => $result['items_count'],
            ]);
        }

        return response()->json([
            'success' => true,
            'version_hash' => $result['version_hash'],
            'items_count' => $result['items_count'],
            'sede' => $result['sede'],
            'items' => $result['items'] ?? $result['playlist'],
            'playlist' => $result['playlist'],
            'settings' => $result['settings'],
        ]);
    }
}
