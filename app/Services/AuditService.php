<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log a system activity event with automatic user and IP context
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $entity = null,
        ?int $sedeId = null,
        array $details = [],
        ?User $actor = null
    ): AuditLog {
        // Resolve actor
        $user = $actor ?: Auth::user();
        if (!$user && request()->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken(request()->bearerToken());
            if ($token && $token->tokenable instanceof User) {
                $user = $token->tokenable;
            }
        }

        $userId = $user ? $user->id : null;
        $userName = $user ? $user->name : 'Sistema / Automático';

        // Resolve sede if available
        $sedeName = null;
        if ($sedeId) {
            $sede = Sede::find($sedeId);
            $sedeName = $sede ? $sede->name : null;
        } elseif ($entity && isset($entity->sede_id)) {
            $sedeId = $entity->sede_id;
            $sede = Sede::find($sedeId);
            $sedeName = $sede ? $sede->name : null;
        } elseif ($entity instanceof Sede) {
            $sedeId = $entity->id;
            $sedeName = $entity->name;
        }

        $entityType = $entity ? class_basename($entity) : null;
        $entityId = $entity ? $entity->getKey() : null;

        if (!$description) {
            $description = "Acción {$action} ejecutada por {$userName}";
        }

        return AuditLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'sede_id' => $sedeId,
            'sede_name' => $sedeName,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'details' => !empty($details) ? $details : null,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? 'Desconocido', 0, 255),
            'created_at' => now(),
        ]);
    }

    /**
     * Get paginated audit logs with flexible filters
     */
    public function getLogs(array $filters = [], int $perPage = 25)
    {
        $query = AuditLog::with('user')->latest('created_at');

        if (!empty($filters['sede_id'])) {
            $query->where('sede_id', (int) $filters['sede_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', 'like', $filters['action'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('sede_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }
}
