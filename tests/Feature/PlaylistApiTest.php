<?php

namespace Tests\Feature;

use App\Models\Sede;
use Tests\TestCase;

class PlaylistApiTest extends TestCase
{
    public function test_playlist_requires_slug_or_sede_id(): void
    {
        $response = $this->getJson('/api/playlist');

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_playlist_returns_data_for_valid_slug(): void
    {
        $sede = Sede::where('is_active', true)->first();
        $this->assertNotNull($sede, 'Must have at least one active sede');

        $response = $this->getJson('/api/playlist?slug=' . $sede->slug);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'version_hash',
                'sede' => ['id', 'name', 'slug', 'color'],
                'items',
                'settings',
            ]);
    }

    public function test_playlist_hot_reload_check_version(): void
    {
        $sede = Sede::where('is_active', true)->first();
        $this->assertNotNull($sede);

        $response = $this->getJson('/api/playlist?slug=' . $sede->slug . '&check_version=1');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'version_hash',
                'items_count',
            ]);
    }

    public function test_playlist_returns_404_for_nonexistent_sede(): void
    {
        $response = $this->getJson('/api/playlist?slug=sede-inexistente-12345');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
