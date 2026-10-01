<?php

namespace Tests\Feature;

use App\Models\Sede;
use Tests\TestCase;

class SedesApiTest extends TestCase
{
    public function test_can_list_sedes(): void
    {
        $response = $this->getJson('/api/sedes');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'color', 'icon', 'is_active', 'total_media'],
                ],
            ]);
    }

    public function test_can_create_sede(): void
    {
        $uniqueName = 'Sede Test ' . time();
        $response = $this->postJson('/api/sedes', [
            'name' => $uniqueName,
            'description' => 'Sede creada en pruebas automáticas',
            'address' => 'Calle 100 # 50 - 20',
            'color' => '#10b981',
            'icon' => 'Building2',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', $uniqueName);

        // Clean up
        $sedeId = $response->json('data.id');
        Sede::destroy($sedeId);
    }

    public function test_can_update_sede(): void
    {
        $sede = Sede::first();
        $this->assertNotNull($sede, 'Must have at least one seed Sede');

        $response = $this->putJson('/api/sedes/' . $sede->id, [
            'description' => 'Descripción actualizada en pruebas',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.description', 'Descripción actualizada en pruebas');
    }

    public function test_can_reorder_sedes(): void
    {
        $sedes = Sede::limit(2)->get();
        if ($sedes->count() >= 2) {
            $orders = [
                ['id' => $sedes[0]->id, 'order_num' => 10],
                ['id' => $sedes[1]->id, 'order_num' => 20],
            ];

            $response = $this->postJson('/api/sedes/reorder', [
                'orders' => $orders,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Orden guardado exitosamente',
                ]);
        }
    }
}
