<?php

namespace App\Services;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SedeService
{
    /**
     * List all sedes
     */
    public function getAll(bool $publicOnly = false)
    {
        $query = Sede::query();

        if ($publicOnly) {
            $query->where('is_active', true);
        }

        return $query->withCount([
            'mediaItems as total_media',
            'activeMediaItems as active_media',
            'mediaItems as total_videos' => function ($q) {
                $q->where('type', 'video');
            },
            'mediaItems as total_images' => function ($q) {
                $q->where('type', 'image');
            },
        ])
            ->orderBy('order_num', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Find by ID
     */
    public function findById(int $id): ?Sede
    {
        return Sede::find($id);
    }

    /**
     * Find by Slug
     */
    public function findBySlug(string $slug): ?Sede
    {
        return Sede::where('slug', trim($slug))->first();
    }

    /**
     * Create a new Sede
     */
    public function create(array $data, ?User $actor = null): Sede
    {
        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);

        // Ensure unique slug
        $baseSlug = $slug;
        $count = 1;
        while (Sede::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        $orderNum = isset($data['order_num']) ? (int) $data['order_num'] : (Sede::max('order_num') + 1);

        $sede = Sede::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'color' => $data['color'] ?? '#2563eb',
            'theme' => $data['theme'] ?? null,
            'icon' => $data['icon'] ?? 'Building2',
            'order_num' => $orderNum,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_by_user_id' => $actor ? $actor->id : null,
        ]);

        AuditService::log(
            'sede.create',
            "Se creó la sede '{$sede->name}'",
            $sede,
            $sede->id,
            ['slug' => $sede->slug, 'color' => $sede->color],
            $actor
        );

        return $sede;
    }

    /**
     * Update Sede
     */
    public function update(Sede $sede, array $data, ?User $actor = null): Sede
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = trim($data['name']);
        }
        if (isset($data['slug'])) {
            $slug = Str::slug($data['slug']);
            if ($slug !== $sede->slug) {
                $baseSlug = $slug;
                $count = 1;
                while (Sede::where('slug', $slug)->where('id', '!=', $sede->id)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $updateData['slug'] = $slug;
            }
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('address', $data)) {
            $updateData['address'] = $data['address'];
        }
        if (isset($data['color'])) {
            $updateData['color'] = $data['color'];
        }
        if (array_key_exists('theme', $data)) {
            $updateData['theme'] = !empty($data['theme']) ? $data['theme'] : null;
        }
        if (isset($data['icon'])) {
            $updateData['icon'] = $data['icon'];
        }
        if (isset($data['order_num'])) {
            $updateData['order_num'] = (int) $data['order_num'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        $sede->update($updateData);

        AuditService::log(
            'sede.update',
            "Se actualizaron los datos de la sede '{$sede->name}'",
            $sede,
            $sede->id,
            array_keys($updateData),
            $actor
        );

        return $sede;
    }

    /**
     * Delete Sede with related media
     */
    public function delete(Sede $sede, ?User $actor = null): bool
    {
        $name = $sede->name;
        $sedeId = $sede->id;

        AuditService::log(
            'sede.delete',
            "Se eliminó la sede '{$name}' y sus medios asociados",
            null,
            $sedeId,
            ['deleted_sede_name' => $name],
            $actor
        );

        return (bool) $sede->delete();
    }

    /**
     * Reorder sedes in a single database transaction
     */
    public function reorder(array $orders, ?User $actor = null): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                if (isset($item['id']) && isset($item['order_num'])) {
                    Sede::where('id', (int) $item['id'])->update(['order_num' => (int) $item['order_num']]);
                }
            }
        });

        AuditService::log(
            'sede.reorder',
            'Se actualizó la secuencia de visualización de las sedes',
            null,
            null,
            ['total_reordered' => count($orders)],
            $actor
        );
    }
}
