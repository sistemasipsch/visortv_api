<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\AddUrlMediaRequest;
use App\Http\Requests\Media\BulkDeleteMediaRequest;
use App\Http\Requests\Media\ReorderMediaRequest;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\MediaItemResource;
use App\Services\AuthService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    protected MediaService $mediaService;
    protected AuthService $authService;

    public function __construct(MediaService $mediaService, AuthService $authService)
    {
        $this->mediaService = $mediaService;
        $this->authService = $authService;
    }

    /**
     * Backward-compatibility handler for legacy /media.php
     */
    public function handle(Request $request)
    {
        $action = $request->query('action', $request->input('action', ''));
        $id = $request->query('id', $request->input('id'));

        if ($action === 'add-url' && $request->isMethod('post')) {
            return $this->addUrl(app(AddUrlMediaRequest::class));
        }

        if ($action === 'reorder' && $request->isMethod('post')) {
            return $this->reorder(app(ReorderMediaRequest::class));
        }

        if ($action === 'bulk-delete' && $request->isMethod('post')) {
            return $this->bulkDelete(app(BulkDeleteMediaRequest::class));
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
            return $this->store(app(StoreMediaRequest::class));
        }

        if ($request->isMethod('put') && $id !== null) {
            return $this->update(app(UpdateMediaRequest::class), $id);
        }

        if ($request->isMethod('delete') && $id !== null) {
            return $this->destroy($request, $id);
        }

        return response()->json(['success' => false, 'error' => 'Método no permitido'], 405);
    }

    /**
     * List media items for a sede
     */
    public function index(Request $request): JsonResponse
    {
        $sedeId = $request->query('sede_id', $request->input('sede_id'));
        if (!$sedeId) {
            return response()->json(['success' => false, 'error' => 'El ID de la sede es requerido'], 400);
        }

        $activeOnly = $request->query('active_only') === '1';
        $items = $this->mediaService->getBySede((int) $sedeId, $activeOnly);

        return response()->json([
            'success' => true,
            'data' => MediaItemResource::collection($items),
        ]);
    }

    /**
     * Show single media item
     */
    public function show($id): JsonResponse
    {
        $media = $this->mediaService->findById((int) $id);
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Archivo multimedia no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new MediaItemResource($media),
        ]);
    }

    /**
     * Upload and store file media item
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $sedeId = (int) $validated['sede_id'];
        $actor = $this->authService->resolveUser($request);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $created = [];
            foreach ($files as $file) {
                $created[] = $this->mediaService->storeUpload($file, $sedeId, $validated, $actor);
            }

            return response()->json([
                'success' => true,
                'message' => count($created) . ' archivo(s) cargado(s) exitosamente',
                'data' => MediaItemResource::collection($created),
            ], 201);
        }

        $file = $request->file('file');
        $media = $this->mediaService->storeUpload($file, $sedeId, $validated, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Archivo multimedia cargado y procesado exitosamente',
            'data' => new MediaItemResource($media),
        ], 201);
    }

    /**
     * Add external streaming URL media item
     */
    public function addUrl(AddUrlMediaRequest $request): JsonResponse
    {
        $actor = $this->authService->resolveUser($request);
        $media = $this->mediaService->storeUrl($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Contenido web agregado exitosamente',
            'data' => new MediaItemResource($media),
        ], 201);
    }

    /**
     * Update media item attributes
     */
    public function update(UpdateMediaRequest $request, $id): JsonResponse
    {
        $media = $this->mediaService->findById((int) $id);
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Archivo multimedia no encontrado'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $updatedMedia = $this->mediaService->update($media, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Contenido multimedia actualizado exitosamente',
            'data' => new MediaItemResource($updatedMedia),
        ]);
    }

    /**
     * Delete media item (Safe Unlink)
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $media = $this->mediaService->findById((int) $id);
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Archivo multimedia no encontrado'], 404);
        }

        $actor = $this->authService->resolveUser($request);
        $this->mediaService->delete($media, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Contenido multimedia eliminado exitosamente',
        ]);
    }

    /**
     * Bulk delete media items
     */
    public function bulkDelete(BulkDeleteMediaRequest $request): JsonResponse
    {
        $ids = $request->validated()['ids'];
        $actor = $this->authService->resolveUser($request);
        $count = $this->mediaService->bulkDelete($ids, $actor);

        return response()->json([
            'success' => true,
            'message' => "{$count} archivo(s) eliminado(s) exitosamente",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Reorder media items
     */
    public function reorder(ReorderMediaRequest $request): JsonResponse
    {
        $actor = $this->authService->resolveUser($request);
        $this->mediaService->reorder($request->validated()['orders'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Orden de reproducción guardado exitosamente',
        ]);
    }
}
