<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get paginated audit activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'sede_id' => $request->query('sede_id'),
            'user_id' => $request->query('user_id'),
            'action' => $request->query('action'),
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $perPage = min(100, max(5, (int) $request->query('per_page', 25)));
        $logs = $this->auditService->getLogs($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs->items()),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
