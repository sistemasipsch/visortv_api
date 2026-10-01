<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Retrieve all system settings
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'settings' => Setting::getAllSettings(),
        ]);
    }

    /**
     * Update system settings
     */
    public function update(Request $request): JsonResponse
    {
        $allowedKeys = [
            'app_name',
            'app_author',
            'default_image_duration',
            'tv_show_clock',
            'tv_show_date',
            'tv_show_sede_title',
            'tv_show_progress_bar',
            'tv_auto_refresh_seconds',
            'tv_transition_effect',
            'tv_ticker_enabled',
            'tv_ticker_message',
        ];

        $input = $request->all();
        $updatedKeys = [];

        foreach ($input as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
                $updatedKeys[] = $key;
            }
        }

        $actor = $this->authService->resolveUser($request);
        $actorName = $actor ? $actor->name : 'Ashly Nicole';

        AuditService::log(
            'setting.update',
            "{$actorName} actualizó las configuraciones del sistema",
            null,
            null,
            ['updated_keys' => $updatedKeys],
            $actor
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente',
            'settings' => Setting::getAllSettings(),
        ]);
    }

    /**
     * Legacy entry point for settings.php
     */
    public function handle(Request $request): JsonResponse
    {
        if ($request->isMethod('post') || $request->isMethod('put')) {
            return $this->update($request);
        }
        return $this->index();
    }
}
