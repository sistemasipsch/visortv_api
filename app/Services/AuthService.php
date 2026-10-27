<?php

namespace App\Services;

use App\Models\User;
use App\Mail\PasswordResetCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * Send password reset code (6 digits) via email or return hint/dev code
     */
    public function sendResetCode(string $identifier): array
    {
        $identifier = trim($identifier);

        $user = User::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'username' => ['No se encontró ningún usuario con ese nombre de usuario o correo electrónico.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Esta cuenta de usuario se encuentra temporalmente inactiva. Contacte al administrador.'],
            ]);
        }

        // Generate a cryptographically secure 6-digit numeric code
        $code = sprintf('%06d', random_int(100000, 999999));
        $tokenKey = $user->email ?: $user->username;

        // Upsert into password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $tokenKey],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // Generate masked email hint if email exists
        $maskedEmail = null;
        if (!empty($user->email)) {
            $parts = explode('@', $user->email);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $len = strlen($name);
            if ($len <= 2) {
                $maskedName = substr($name, 0, 1) . '*';
            } else {
                $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, $len - 2)) . substr($name, -1);
            }
            $maskedEmail = $maskedName . '@' . $domain;
        }

        // Attempt sending email if email address is configured
        $emailSent = false;
        if (!empty($user->email)) {
            try {
                Mail::to($user->email)->send(new PasswordResetCodeMail($user, $code));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::warning("Error enviando correo de recuperación a {$user->email}: " . $e->getMessage());
            }
        }

        AuditService::log(
            'auth.password_reset_requested',
            "Se solicitó código de recuperación de contraseña para {$user->name} (@{$user->username})",
            $user,
            null,
            ['email_sent' => $emailSent, 'has_email' => !empty($user->email)],
            $user
        );

        $isDevOrLog = config('app.debug') || config('mail.default') === 'log' || app()->environment('testing', 'local');

        $message = !empty($user->email)
            ? ($emailSent
                ? "Hemos enviado un código de verificación de 6 dígitos a su correo {$maskedEmail}."
                : "Se generó el código de verificación para {$maskedEmail}. (Servicio de correo no disponible o en modo registro).")
            : "Se generó el código de verificación para el usuario @{$user->username}.";

        return [
            'success' => true,
            'message' => $message,
            'has_email' => !empty($user->email),
            'email_hint' => $maskedEmail,
            'username' => $user->username,
            'dev_code' => $isDevOrLog ? $code : null,
        ];
    }

    /**
     * Reset password using 6-digit code
     */
    public function resetPasswordWithCode(string $identifier, string $code, string $newPassword): array
    {
        $identifier = trim($identifier);
        $code = trim($code);

        $user = User::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'username' => ['No se encontró ningún usuario con ese identificador.'],
            ]);
        }

        $tokenKey = $user->email ?: $user->username;

        $record = DB::table('password_reset_tokens')->where('email', $tokenKey)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => ['No se ha solicitado un código de recuperación o ya ha sido utilizado.'],
            ]);
        }

        // Check if code has expired (15 minutes lifespan)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $tokenKey)->delete();
            throw ValidationException::withMessages([
                'code' => ['El código de verificación ha expirado (validez: 15 minutos). Por favor solicita uno nuevo.'],
            ]);
        }

        // Validate code hash
        if (!Hash::check($code, $record->token)) {
            throw ValidationException::withMessages([
                'code' => ['El código de verificación de 6 dígitos es incorrecto.'],
            ]);
        }

        // Update password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Delete token and invalidate active sessions/tokens
        DB::table('password_reset_tokens')->where('email', $tokenKey)->delete();
        $user->tokens()->delete();

        AuditService::log(
            'auth.password_reset_completed',
            "{$user->name} (@{$user->username}) restableció exitosamente su contraseña",
            $user,
            null,
            [],
            $user
        );

        return [
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.',
            'username' => $user->username,
        ];
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
