<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Tests\TestCase;

class AuditApiTest extends TestCase
{
    public function test_can_list_audit_logs(): void
    {
        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'user_name', 'action', 'description', 'created_at_human'],
                ],
                'pagination' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_can_filter_audit_logs_by_action(): void
    {
        $response = $this->getJson('/api/audit-logs?action=media');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
