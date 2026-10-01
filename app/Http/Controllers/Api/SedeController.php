<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sede\ReorderSedesRequest;
use App\Http\Requests\Sede\StoreSedeRequest;
use App\Http\Requests\Sede\UpdateSedeRequest;
use App\Http\Resources\SedeResource;
use App\Services\AuthService;
use App\Services\SedeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    protected SedeService $sedeService;
    protected AuthService $authService;

    public function __construct(SedeService $sedeService, AuthService $authService)
    {
        $this->sedeService = $sedeService;
        $this->authService = $authService;
    }

    /**
     * Backward-compatibility handler for legacy /sedes.php
     */
    public function handle(Request $request)
    {
        $action = $request->query('action', $request->input('action', ''));
        $id = $request->query('id', $request->input('id'));

        if ($action === 'reorder' && $request->isMethod('post')) {
            return $this->reorder(app(ReorderSedesRequest::class));
        }

        if ($action === 'delete' || ($request->isMethod('delete') && $id !== null)) {
            $deleteId = $id ?: $request->input('id');
            if ($deleteId) {
                return $this->destroy($request, $deleteId);
            }
        }

        if ($request->isMethod('get')) {
            if ($id !== null) {
                return $this->show($id);
            }
            return $this->index($request);
        }

        if ($request->isMethod('post')) {
            return $this->store(app(StoreSedeRequest::class));
        }

        if ($request->isMethod('put') && $id !== null) {
            return $this->update(app(UpdateSedeRequest::class), $id);
        }

        if ($request->isMethod('delete') && $id !== null) {
            return $this->destroy($request, $id);
        }

        return response()->json(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    /**
     * List all sedes
     */
    public function index(Request $request): JsonResponse
    {
        $publicOnly = $request->boolean('public') || $request->query('public') === '1';
        $sedes = $this->sedeService->getAll($publicOnly);

        return response()->json([
            'success' => true,
            'data' => SedeResource::collection($sedes),
        ]);
    }

    /**
     * Show a single sede
     */
    public function show($identifier): JsonResponse
    {
        $sede = is_numeric($identifier)
            ? $this->sedeService->findById((int) $identifier)
            : $this->sedeService->findBySlug($identifier);

        if (!$sede) {
            return response()->json(['success' => false, 'error' => 'Sede no encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SedeResource($sede),
        ]);
    }

    /**
     * Create a new sede
     */
    public function store(StoreSedeRequest $request): JsonResponse
    {
        $actor = $this->authService->resolveUser($request);
        $sede = $this->sedeService->create($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => "Sede '{$sede->name}' creada exitosamente",
            'data' => new SedeResource($sede),
        ], 201);
    }

    /**
     * Update an existing sede
     */
    public function update(UpdateSedeRequest $request, $id): JsonResponse
    {
        $sede = $this->sedeService->findById((int) $id);
        if (!$sede) {
            return response()->json(['success' => false, 'error' => 'Sede no encontrada'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $updatedSede = $this->sedeService->update($sede, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Sede actualizada exitosamente',
            'data' => new SedeResource($updatedSede),
        ]);
    }

    /**
     * Delete a sede
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $sede = $this->sedeService->findById((int) $id);
        if (!$sede) {
            return response()->json(['success' => false, 'error' => 'Sede no encontrada'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $this->sedeService->delete($sede, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Sede eliminada exitosamente',
        ]);
    }

    /**
     * Reorder sedes
     */
    public function reorder(ReorderSedesRequest $request): JsonResponse
    {
        $actor = $this->authService->resolveUser($request);
        $this->sedeService->reorder($request->validated()['orders'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Orden guardado exitosamente',
        ]);
    }
}
