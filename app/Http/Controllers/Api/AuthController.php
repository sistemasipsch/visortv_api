<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Backward-compatibility handler for legacy /auth.php
     */
    public function handle(Request $request)
    {
        $action = $request->query('action', 'login');
        return match ($action) {
            'login' => $this->login(app(LoginRequest::class)),
            'me' => $this->me($request),
            'logout' => $this->logout($request),
            'change-password' => $this->changePassword(app(ChangePasswordRequest::class)),
            'update-profile' => $this->updateProfile(app(UpdateProfileRequest::class)),
            default => response()->json(['success' => false, 'error' => 'Acción no encontrada'], 404),
        };
    }

    /**
     * User Login and Token Generation
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->authService->login(
            $credentials['username'],
            $credentials['password'],
            $request->ip(),
            $request->userAgent()
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $result['status'] ?? 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ]);
    }

    /**
     * Get Current Authenticated User
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->resolveUser($request);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'No autenticado'], 401);
        }

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * User Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $this->authService->resolveUser($request);

        if ($user) {
            $this->authService->logout($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Change User Password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resolveUser($request);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'No autenticado'], 401);
        }

        $validated = $request->validated();
        $this->authService->changePassword($user, $validated['current_password'], $validated['new_password']);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
        ]);
    }

    /**
     * Update User Profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->resolveUser($request);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'No autenticado'], 401);
        }

        $updatedUser = $this->authService->updateProfile($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'user' => new UserResource($updatedUser),
        ]);
    }

    /**
     * Update Current User Avatar (File or String)
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $this->authService->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if ($request->hasFile('avatar') || $request->hasFile('file')) {
            $request->validate([
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
                'file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            ]);
            $file = $request->file('avatar') ?: $request->file('file');
            $userService = app(\App\Services\UserService::class);
            $updatedUser = $userService->uploadAvatar($user, $file, $user);
        } else {
            $avatar = $request->input('avatar');
            $updatedUser = $this->authService->updateProfile($user, ['avatar' => $avatar]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar actualizado exitosamente',
            'avatar' => $updatedUser->avatar,
            'user' => new UserResource($updatedUser),
            'data' => new UserResource($updatedUser),
        ]);
    }
}
