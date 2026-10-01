<?php

namespace Tests\Unit;

use App\Services\ImageOptimizerService;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    public function test_optimizer_instantiation_and_thumbnail_path(): void
    {
        $optimizer = new ImageOptimizerService();
        $thumbPath = $optimizer->getThumbnailPath('/var/www/storage/media/photo.jpg');

        $this->assertStringContainsString('thumb_photo.jpg', $thumbPath);
    }

    public function test_optimizer_handles_nonexistent_file_gracefully(): void
    {
        $optimizer = new ImageOptimizerService();
        $result = $optimizer->optimize('/path/to/nonexistent/file.jpg', 'jpg');

        $this->assertFalse($result['optimized']);
    }

    public function test_optimizer_ignores_unsupported_extensions(): void
    {
        $optimizer = new ImageOptimizerService();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_media_');
        file_put_contents($tempFile, 'fake video stream');

        $result = $optimizer->optimize($tempFile, 'mp4');
        $this->assertFalse($result['optimized']);

        @unlink($tempFile);
    }

    public function test_optimizer_processes_gd_image_if_available(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        $optimizer = new ImageOptimizerService();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';

        // Create a 100x100 test image using GD
        $im = imagecreatetruecolor(100, 100);
        $blue = imagecolorallocate($im, 0, 100, 255);
        imagefilledrectangle($im, 0, 0, 99, 99, $blue);
        imagejpeg($im, $tempFile, 90);
        imagedestroy($im);

        $result = $optimizer->optimize($tempFile, 'jpg');

        $this->assertTrue($result['optimized']);
        $this->assertEquals(100, $result['width']);
        $this->assertEquals(100, $result['height']);

        $thumbFile = $optimizer->getThumbnailPath($tempFile);
        $this->assertFileExists($thumbFile);

        @unlink($tempFile);
        @unlink($thumbFile);
    }
}
