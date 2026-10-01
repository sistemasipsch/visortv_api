<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    public function test_can_list_users(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'username', 'role', 'is_active'],
                ],
            ]);
    }

    public function test_can_create_and_toggle_user(): void
    {
        $uniqueUsername = 'operador_' . time();
        $response = $this->postJson('/api/users', [
            'name' => 'Operador de Pruebas',
            'username' => $uniqueUsername,
            'email' => $uniqueUsername . '@visortv.com',
            'password' => 'secret1234',
            'role' => 'operator',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.username', $uniqueUsername);

        $userId = $response->json('data.id');

        // Toggle status
        $toggleRes = $this->postJson('/api/users/' . $userId . '/toggle-status');
        $toggleRes->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.is_active', false);

        // Clean up
        User::destroy($userId);
    }
}
