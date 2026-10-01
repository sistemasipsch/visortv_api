<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use App\Models\Sede;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StreamApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_stream_directory_traversal_is_blocked(): void
    {
        $response = $this->get('/api/media/stream/..%2Fsecret.env');
        $response->assertStatus(400);

        $response2 = $this->get('/api/media/stream/subfolder%2Fsecret.env');
        $response2->assertStatus(400);
    }

    public function test_stream_nonexistent_media_returns_404(): void
    {
        $response = $this->get('/api/media/stream/archivo_inexistente_123.mp4');
        $response->assertStatus(404);
    }

    public function test_stream_image_file_with_etag_and_caching(): void
    {
        $filename = 'test_image.jpg';
        $content = 'Fake JPEG Image Data For Testing Caching And Headers';
        Storage::disk('public')->put('media/' . $filename, $content);

        $response = $this->get('/api/media/stream/' . $filename);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertNotEmpty($response->headers->get('ETag'));

        $etag = $response->headers->get('ETag');

        // Test HTTP 304 Not Modified
        $notModifiedResponse = $this->withHeaders([
            'If-None-Match' => $etag,
        ])->get('/api/media/stream/' . $filename);

        $notModifiedResponse->assertStatus(304);
    }

    public function test_stream_image_head_request(): void
    {
        $filename = 'test_head.png';
        $content = 'PNG Fake Content For Testing HEAD';
        Storage::disk('public')->put('media/' . $filename, $content);

        $response = $this->call('HEAD', '/api/media/stream/' . $filename);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertEmpty($response->getContent());
    }

    public function test_stream_video_full_content(): void
    {
        $filename = 'test_sample.mp4';
        $videoData = str_repeat('A', 2048); // 2KB fake video
        Storage::disk('public')->put('media/' . $filename, $videoData);

        $response = $this->get('/api/media/stream/' . $filename);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_stream_video_range_request_returns_206(): void
    {
        $filename = 'test_range.mp4';
        $videoData = str_repeat('X', 4096); // 4KB fake video
        Storage::disk('public')->put('media/' . $filename, $videoData);

        $response = $this->withHeaders([
            'Range' => 'bytes=0-1023',
        ])->get('/api/media/stream/' . $filename);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-1023/4096');
        $response->assertHeader('Content-Length', '1024');
    }

    public function test_stream_video_invalid_range_returns_416(): void
    {
        $filename = 'test_range_invalid.mp4';
        $videoData = str_repeat('X', 1000);
        Storage::disk('public')->put('media/' . $filename, $videoData);

        $response = $this->withHeaders([
            'Range' => 'bytes=5000-6000',
        ])->get('/api/media/stream/' . $filename);

        $response->assertStatus(416);
    }

    public function test_stream_external_url_redirects(): void
    {
        $sede = Sede::first();
        $this->assertNotNull($sede);

        $media = MediaItem::create([
            'sede_id' => $sede->id,
            'title' => 'External Stream Video',
            'type' => 'video',
            'filename' => 'https://cdn.example.com/video.mp4',
            'is_active' => true,
        ]);

        $response = $this->get('/api/media/stream/' . rawurlencode($media->filename));
        $response->assertRedirect('https://cdn.example.com/video.mp4');
    }

    public function test_stream_thumbnail_fallback_to_original(): void
    {
        $filename = 'banner.jpg';
        Storage::disk('public')->put('media/' . $filename, 'Banner Image');

        // Requesting thumbnail when thumb does not exist should gracefully fallback to original
        $response = $this->get('/api/media/stream/' . $filename . '?thumb=1');
        $response->assertStatus(200);
    }
}
