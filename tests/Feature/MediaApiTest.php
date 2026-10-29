<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use App\Models\Sede;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    public function test_can_add_media_by_external_url(): void
    {
        $sede = Sede::first();
        $this->assertNotNull($sede);

        $response = $this->postJson('/api/media/add-url', [
            'sede_id' => $sede->id,
            'title' => 'Video CDN Externo de Prueba',
            'url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'type' => 'video',
            'duration' => 0,
            'fit_mode' => 'contain',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.title', 'Video CDN Externo de Prueba');

        $mediaId = $response->json('data.id');

        // Test updating media item
        $updateResponse = $this->putJson('/api/media/' . $mediaId, [
            'title' => 'Video CDN Modificado',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Video CDN Modificado');

        // Test deleting media item via DELETE
        $deleteResponse = $this->deleteJson('/api/media/' . $mediaId);
        $deleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Test fallback route POST /api/media/{id}/delete
        $tempItem = MediaItem::create([
            'sede_id' => $sede->id,
            'title' => 'Temp Item for Post Delete',
            'type' => 'image',
            'filename' => 'https://example.com/delete-test.jpg',
            'is_active' => true,
        ]);
        $postDeleteResponse = $this->postJson('/api/media/' . $tempItem->id . '/delete');
        $postDeleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('media_items', ['id' => $tempItem->id]);
    }

    public function test_can_bulk_delete_media_items(): void
    {
        $sede = Sede::first();
        $this->assertNotNull($sede);

        $item1 = MediaItem::create([
            'sede_id' => $sede->id,
            'title' => 'Temp Bulk 1',
            'type' => 'image',
            'filename' => 'https://example.com/image1.jpg',
            'is_active' => true,
        ]);

        $item2 = MediaItem::create([
            'sede_id' => $sede->id,
            'title' => 'Temp Bulk 2',
            'type' => 'image',
            'filename' => 'https://example.com/image2.jpg',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/media/bulk-delete', [
            'ids' => [$item1->id, $item2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '2 archivo(s) eliminado(s) exitosamente',
            ]);
    }

    public function test_can_upload_video_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $sede = Sede::first();
        $this->assertNotNull($sede);

        $fakeVideo = \Illuminate\Http\UploadedFile::fake()->create('promocional.mp4', 15000, 'video/mp4');

        $response = $this->postJson('/api/media', [
            'sede_id' => $sede->id,
            'files' => [$fakeVideo],
            'fit_mode' => 'cover',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.0.type', 'video')
            ->assertJsonPath('data.0.original_name', 'promocional.mp4');

        $filename = $response->json('data.0.filename');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('media/' . $filename);
    }

    public function test_rejects_incompatible_mkv_video_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $sede = Sede::first();
        $this->assertNotNull($sede);

        $fakeMkv = \Illuminate\Http\UploadedFile::fake()->create('pelicula.mkv', 15000, 'video/x-matroska');

        $response = $this->postJson('/api/media', [
            'sede_id' => $sede->id,
            'files' => [$fakeMkv],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files.0']);
    }

    public function test_accepts_valid_jpg_and_png_images(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $sede = Sede::first();
        $this->assertNotNull($sede);

        $fakeJpg = \Illuminate\Http\UploadedFile::fake()->create('anuncio.jpg', 500, 'image/jpeg');
        $fakePng = \Illuminate\Http\UploadedFile::fake()->create('logo.png', 500, 'image/png');

        $response = $this->postJson('/api/media', [
            'sede_id' => $sede->id,
            'files' => [$fakeJpg, $fakePng],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.0.type', 'image')
            ->assertJsonPath('data.1.type', 'image');
    }
}
