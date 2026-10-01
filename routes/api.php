<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SedeController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StreamController;

/*
|--------------------------------------------------------------------------
| Visor TV Systems — Public & High-Performance Endpoints
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
Route::post('/auth/update-profile', [AuthController::class, 'updateProfile']);
Route::post('/auth/update-avatar', [AuthController::class, 'updateAvatar']);

// Smart TV Playlist & Zero-Delay Synchronization
Route::get('/playlist', [PlaylistController::class, 'index']);

// High-speed HTTP 206 Streaming (Partial Content & HEAD requests)
Route::match(['GET', 'HEAD'], '/media/stream/{filename}', [StreamController::class, 'stream'])->where('filename', '.*');

// Sedes (Locations / TV displays)
Route::get('/sedes', [SedeController::class, 'index']);
Route::get('/sedes/{id}', [SedeController::class, 'show']);
Route::post('/sedes', [SedeController::class, 'store']);
Route::put('/sedes/{id}', [SedeController::class, 'update']);
Route::delete('/sedes/{id}', [SedeController::class, 'destroy']);
Route::post('/sedes/reorder', [SedeController::class, 'reorder']);

// Media Management
Route::get('/media', [MediaController::class, 'index']);
Route::post('/media', [MediaController::class, 'store']);
Route::post('/media/add-url', [MediaController::class, 'addUrl']);
Route::post('/media/reorder', [MediaController::class, 'reorder']);
Route::post('/media/bulk-delete', [MediaController::class, 'bulkDelete']);
Route::get('/media/{id}', [MediaController::class, 'show']);
Route::put('/media/{id}', [MediaController::class, 'update']);
Route::delete('/media/{id}', [MediaController::class, 'destroy']);
Route::post('/media/{id}/delete', [MediaController::class, 'destroy']); // Firewall/Proxy fallback

// Audit & System Activity Logs
Route::get('/audit-logs', [AuditController::class, 'index']);

// User Management (Admin & Operators)
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
Route::post('/users/{id}/avatar', [UserController::class, 'uploadAvatar']);

// Dashboard Stats & Settings
Route::get('/stats', [StatsController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
Route::post('/settings', [SettingController::class, 'update']);
Route::put('/settings', [SettingController::class, 'update']);

/*
|--------------------------------------------------------------------------
| Backward-Compatibility / Legacy PHP-Style Routes
|--------------------------------------------------------------------------
*/
Route::any('/auth.php', [AuthController::class, 'handle']);
Route::any('/sedes.php', [SedeController::class, 'handle']);
Route::any('/media.php', [MediaController::class, 'handle']);
Route::any('/playlist.php', [PlaylistController::class, 'index']);
Route::any('/stats.php', [StatsController::class, 'index']);
Route::any('/settings.php', [SettingController::class, 'handle']);
