<?php

namespace App\Services;

class ImageOptimizerService
{
    /**
     * Max dimensions for TV display (Full HD: 1920x1080 is optimal for Smart TV performance)
     */
    protected const MAX_WIDTH = 1920;
    protected const MAX_HEIGHT = 1080;

    /**
     * Optimize an uploaded image:
     * 1. Fix EXIF orientation (prevents photos appearing rotated sideways on TV).
     * 2. Downscale if greater than Full HD to preserve TV RAM and eliminate decoder lag.
     * 3. Strip unnecessary metadata while preserving maximum visual clarity.
     * 4. Generate high-performance thumbnail for instant admin panel loading.
     */
    public function optimize(string $filePath, ?string $extension = null): array
    {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return [
                'optimized' => false,
                'width' => 0,
                'height' => 0,
                'thumbnail' => null,
            ];
        }

        $ext = strtolower($extension ?: pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return [
                'optimized' => false,
                'width' => 0,
                'height' => 0,
                'thumbnail' => null,
            ];
        }

        try {
            // Check memory limit and increase temporarily if processing massive PNG (>15MB)
            $fileSizeBytes = @filesize($filePath) ?: 0;
            if ($fileSizeBytes > 10 * 1024 * 1024) {
                @ini_set('memory_limit', '512M');
            }

            $image = $this->createImageFromFile($filePath, $ext);
            if (!$image) {
                return ['optimized' => false, 'width' => 0, 'height' => 0, 'thumbnail' => null];
            }

            // 1. Correct EXIF Orientation
            if (in_array($ext, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $image = $this->fixOrientation($filePath, $image);
            }

            $origWidth = imagesx($image);
            $origHeight = imagesy($image);

            // 2. Downscale if exceeding Full HD (1920x1080)
            $needsResize = ($origWidth > self::MAX_WIDTH || $origHeight > self::MAX_HEIGHT);
            if ($needsResize) {
                $ratio = min(self::MAX_WIDTH / $origWidth, self::MAX_HEIGHT / $origHeight);
                $newWidth = max(1, (int) round($origWidth * $ratio));
                $newHeight = max(1, (int) round($origHeight * $ratio));

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                $this->preserveTransparency($resized, $ext);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($image);
                $image = $resized;
                $origWidth = $newWidth;
                $origHeight = $newHeight;
            }

            // 3. Save optimized primary image
            $this->saveImage($image, $filePath, $ext, 88);

            // 4. Generate high-performance JPEG thumbnail for instant preview (320x180 max, ~10KB)
            $thumbnailPath = $this->getThumbnailPath($filePath);
            $this->createThumbnailFromResource($image, $thumbnailPath, 'jpg', 320, 180);

            imagedestroy($image);

            // Clear stat cache so filesize reflects optimized size
            clearstatcache(true, $filePath);

            return [
                'optimized' => true,
                'width' => $origWidth,
                'height' => $origHeight,
                'thumbnail' => basename($thumbnailPath),
            ];
        } catch (\Throwable $e) {
            // Failsafe: Never block media upload if image processing throws an exception
            return [
                'optimized' => false,
                'width' => 0,
                'height' => 0,
                'thumbnail' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create standalone thumbnail from an image file on disk
     */
    public function createThumbnail(string $sourcePath, string $targetPath, int $maxWidth = 320, int $maxHeight = 180): bool
    {
        if (!extension_loaded('gd') || !file_exists($sourcePath)) {
            return false;
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        try {
            $image = $this->createImageFromFile($sourcePath, $ext);
            if (!$image) {
                return false;
            }

            $targetExt = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)) ?: 'jpg';
            $success = $this->createThumbnailFromResource($image, $targetPath, $targetExt, $maxWidth, $maxHeight);
            imagedestroy($image);
            return $success;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Correct photo orientation based on camera EXIF tags
     */
    protected function fixOrientation(string $filePath, $image)
    {
        $exif = @exif_read_data($filePath);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
        return $image;
    }

    /**
     * Create image resource from file path based on extension
     */
    protected function createImageFromFile(string $path, string $ext)
    {
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    /**
     * Save GD image resource back to disk
     */
    protected function saveImage($image, string $path, string $ext, int $quality = 88): bool
    {
        return match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, 7),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => imagejpeg($image, $path, $quality),
        };
    }

    /**
     * Preserve alpha transparency for PNG and WebP
     */
    protected function preserveTransparency($image, string $ext): void
    {
        if (in_array($ext, ['png', 'webp'])) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        }
    }

    /**
     * Generate thumbnail from GD resource
     */
    protected function createThumbnailFromResource($sourceImage, string $targetPath, string $ext, int $maxWidth, int $maxHeight): bool
    {
        $srcW = imagesx($sourceImage);
        $srcH = imagesy($sourceImage);

        $ratio = min($maxWidth / max(1, $srcW), $maxHeight / max(1, $srcH));
        $thumbW = max(1, (int) round($srcW * $ratio));
        $thumbH = max(1, (int) round($srcH * $ratio));

        $thumb = imagecreatetruecolor($thumbW, $thumbH);

        if (in_array($ext, ['png', 'webp'])) {
            $this->preserveTransparency($thumb, $ext);
        } else {
            // White background for JPEG thumbnails
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefilledrectangle($thumb, 0, 0, $thumbW, $thumbH, $white);
        }

        imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $thumbW, $thumbH, $srcW, $srcH);

        $saved = $this->saveImage($thumb, $targetPath, $ext, 80);
        imagedestroy($thumb);

        return $saved;
    }

    /**
     * Compute thumbnail file path (always .jpg for ultra-lightweight ~10KB size)
     */
    public function getThumbnailPath(string $filePath): string
    {
        $dir = dirname($filePath);
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        return $dir . DIRECTORY_SEPARATOR . 'thumb_' . $baseName . '.jpg';
    }
}
