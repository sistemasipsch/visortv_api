<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;
    protected AuthService $authService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $users = $this->userService->listUsers($search);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $this->authService->resolveUser($request);
        $user = $this->userService->createUser($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => "Usuario '{$user->name}' creado exitosamente",
            'data' => new UserResource($user),
        ], 201);
    }

    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $updatedUser = $this->userService->updateUser($user, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'data' => new UserResource($updatedUser),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $this->userService->deleteUser($user, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado del sistema',
        ]);
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $updatedUser = $this->userService->toggleStatus($user, $actor);

        return response()->json([
            'success' => true,
            'message' => $updatedUser->is_active ? 'Usuario habilitado' : 'Usuario deshabilitado',
            'data' => new UserResource($updatedUser),
        ]);
    }

    public function uploadAvatar(Request $request, $id): JsonResponse
    {
        $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
        }

        $file = $request->file('avatar') ?: $request->file('file');
        if (!$file) {
            return response()->json(['success' => false, 'error' => 'No se proporcionó ningún archivo de imagen'], 422);
        }

        $actor = $this->authService->resolveUser($request);
        $updatedUser = $this->userService->uploadAvatar($user, $file, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada exitosamente',
            'avatar' => $updatedUser->avatar,
            'data' => new UserResource($updatedUser),
            'user' => new UserResource($updatedUser),
        ]);
    }
}
