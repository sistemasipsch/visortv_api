<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Resolve authenticated user from Sanctum or Bearer Token
     */
    public function resolveUser(Request $request): ?User
    {
        $user = $request->user('sanctum') ?? $request->user();
        if ($user) {
            return $user;
        }

        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable instanceof User) {
                return $accessToken->tokenable;
            }
        }

        return null;
    }

    /**
     * Login user and issue API Bearer Token
     */
    public function login(string $username, string $password, ?string $ip = null, ?string $userAgent = null): array
    {
        $username = trim($username);

        $user = User::where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            AuditService::log(
                'auth.failed',
                "Intento fallido de inicio de sesión con usuario '{$username}'",
                null,
                null,
                ['username_attempt' => $username]
            );

            return [
                'error' => 'Credenciales incorrectas',
                'status' => 401,
            ];
        }

        if (!$user->is_active) {
            AuditService::log(
                'auth.blocked',
                "Intento de acceso bloqueado para usuario inactivo '{$user->username}'",
                $user
            );

            return [
                'error' => 'Esta cuenta de usuario se encuentra temporalmente inactiva.',
                'status' => 403,
            ];
        }

        // Update login stats
        $user->last_login_at = now();
        $user->last_login_ip = $ip;
        $user->save();

        // Revoke old tokens & create fresh one
        $user->tokens()->delete();
        $token = $user->createToken('visor_tv_auth')->plainTextToken;

        AuditService::log(
            'auth.login',
            "{$user->name} (@{$user->username}) inició sesión exitosamente",
            $user,
            null,
            ['role' => $user->role],
            $user
        );

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * Change user password securely
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual ingresada es incorrecta.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        AuditService::log(
            'auth.change_password',
            "{$user->name} actualizó su contraseña de acceso",
            $user,
            null,
            [],
            $user
        );

        return true;
    }

    /**
     * Update user profile info
     */
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->name = trim($data['name']);
        }
        if (isset($data['email'])) {
            $user->email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
        }
        if (isset($data['avatar'])) {
            $user->avatar = $data['avatar'];
        }
        if (isset($data['theme'])) {
            $user->theme = $data['theme'];
        }

        $user->save();

        AuditService::log(
            'auth.update_profile',
            "{$user->name} actualizó su perfil personal",
            $user,
            null,
            [],
            $user
        );

        return $user;
    }

    /**
     * Logout and revoke tokens
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();

        AuditService::log(
            'auth.logout',
            "{$user->name} cerró su sesión",
            $user,
            null,
            [],
            $user
        );
    }
}
