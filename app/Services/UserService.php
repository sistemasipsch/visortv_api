<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * List all users with optional search
     */
    public function listUsers(?string $search = null)
    {
        $query = User::query()->orderBy('name', 'asc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Create a new system user
     */
    public function createUser(array $data, ?User $actor = null): User
    {
        $role = $data['role'] ?? 'operator';
        if (!in_array($role, ['superadmin', 'admin', 'operator'])) {
            $role = 'operator';
        }

        $user = User::create([
            'name' => trim($data['name']),
            'username' => strtolower(trim($data['username'])),
            'email' => !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            'password' => Hash::make($data['password']),
            'role' => $role,
            'avatar' => $data['avatar'] ?? null,
            'theme' => $data['theme'] ?? 'dark-blue',
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        AuditService::log(
            'user.create',
            "Se creó el usuario '{$user->name}' (@{$user->username}) con rol '{$user->role}'",
            $user,
            null,
            ['username' => $user->username, 'role' => $user->role],
            $actor
        );

        return $user;
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data, ?User $actor = null): User
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = trim($data['name']);
        }
        if (isset($data['username'])) {
            $updateData['username'] = strtolower(trim($data['username']));
        }
        if (array_key_exists('email', $data)) {
            $updateData['email'] = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
        }
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }
        if (isset($data['role'])) {
            $role = $data['role'];
            if (in_array($role, ['superadmin', 'admin', 'operator'])) {
                // Prevent demoting last superadmin
                if ($user->role === 'superadmin' && $role !== 'superadmin') {
                    $superadminCount = User::where('role', 'superadmin')->where('is_active', true)->count();
                    if ($superadminCount <= 1) {
                        throw ValidationException::withMessages([
                            'role' => ['No se puede degradar al único Superadministrador activo del sistema.'],
                        ]);
                    }
                }
                $updateData['role'] = $role;
            }
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }
        if (array_key_exists('avatar', $data)) {
            $updateData['avatar'] = !empty($data['avatar']) ? $data['avatar'] : null;
        }
        if (array_key_exists('theme', $data)) {
            $updateData['theme'] = !empty($data['theme']) ? $data['theme'] : 'dark-blue';
        }

        $user->update($updateData);

        AuditService::log(
            'user.update',
            "Se actualizaron los datos del usuario '{$user->name}' (@{$user->username})",
            $user,
            null,
            array_keys($updateData),
            $actor
        );

        return $user;
    }

    /**
     * Upload and update avatar file for user
     */
    public function uploadAvatar(User $user, $file, ?User $actor = null): User
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $extension;
        $destPath = storage_path('app/public/media');

        if (!file_exists($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $file->move($destPath, $filename);
        $avatarUrl = '/api/media/stream/' . $filename;

        $user->update(['avatar' => $avatarUrl]);

        AuditService::log(
            'user.avatar',
            "Se actualizó la foto de perfil del usuario '{$user->name}'",
            $user,
            null,
            ['avatar' => $avatarUrl],
            $actor
        );

        return $user;
    }

    /**
     * Delete user safely
     */
    public function deleteUser(User $user, ?User $actor = null): bool
    {
        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propia cuenta de usuario activa.'],
            ]);
        }

        if ($user->role === 'superadmin') {
            $superadminCount = User::where('role', 'superadmin')->count();
            if ($superadminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => ['No se puede eliminar la cuenta principal de Superadministrador.'],
                ]);
            }
        }

        $name = $user->name;
        $username = $user->username;

        AuditService::log(
            'user.delete',
            "Se eliminó el usuario '{$name}' (@{$username}) del sistema",
            null,
            null,
            ['deleted_username' => $username],
            $actor
        );

        return (bool) $user->delete();
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(User $user, ?User $actor = null): User
    {
        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['No puedes desactivar tu propia cuenta activa.'],
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'activada' : 'desactivada';

        AuditService::log(
            'user.status_toggle',
            "La cuenta de '{$user->name}' (@{$user->username}) fue {$statusStr}",
            $user,
            null,
            ['is_active' => $user->is_active],
            $actor
        );

        return $user;
    }
}
