<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatsAndSettingsApiTest extends TestCase
{
    public function test_stats_endpoint_returns_system_overview(): void
    {
        $response = $this->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'stats' => [
                    'total_sedes',
                    'active_sedes',
                    'total_media',
                    'total_videos',
                    'total_images',
                    'active_media',
                    'total_storage_bytes',
                    'formatted_storage',
                ],
                'sedes',
                'recent_media',
                'system' => [
                    'php_version',
                    'laravel_version',
                    'upload_max_filesize',
                    'post_max_size',
                ],
            ]);
    }

    public function test_can_retrieve_and_update_settings(): void
    {
        $getResponse = $this->getJson('/api/settings');

        $getResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'settings' => [
                    'tv_show_clock',
                    'tv_show_sede_title',
                    'tv_show_progress_bar',
                ],
            ]);

        $updateResponse = $this->postJson('/api/settings', [
            'tv_show_clock' => 'true',
            'tv_auto_refresh_seconds' => '30',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('settings.tv_show_clock', 'true')
            ->assertJsonPath('settings.tv_auto_refresh_seconds', '30');
    }
}
