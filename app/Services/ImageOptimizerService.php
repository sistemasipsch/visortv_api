<?php

namespace App\Services;

class ImageOptimizerService
{
    /**
     * Max dimensions for TV display (Full 4K UHD: 3840x2160)
     */
    protected const MAX_WIDTH = 3840;
    protected const MAX_HEIGHT = 2160;

    /**
     * Optimize an uploaded image:
     * 1. Fix EXIF orientation (prevents photos appearing rotated sideways on TV).
     * 2. Downscale if greater than 4K UHD to preserve TV RAM and eliminate decoder lag.
     * 3. Strip unnecessary metadata while preserving maximum visual clarity.
     * 4. Generate high-performance thumbnail for instant admin panel loading.
     */
    public function optimize(string $filePath, string $extension): array
    {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return [
                'optimized' => false,
                'width' => 0,
                'height' => 0,
                'thumbnail' => null,
            ];
        }

        $ext = strtolower($extension);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return [
                'optimized' => false,
                'width' => 0,
                'height' => 0,
                'thumbnail' => null,
            ];
        }

        try {
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

            // 2. Downscale if exceeding 4K UHD
            $needsResize = ($origWidth > self::MAX_WIDTH || $origHeight > self::MAX_HEIGHT);
            if ($needsResize) {
                $ratio = min(self::MAX_WIDTH / $origWidth, self::MAX_HEIGHT / $origHeight);
                $newWidth = (int) round($origWidth * $ratio);
                $newHeight = (int) round($origHeight * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                $this->preserveTransparency($resized, $ext);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($image);
                $image = $resized;
                $origWidth = $newWidth;
                $origHeight = $newHeight;
            }

            // 3. Save optimized primary image
            $this->saveImage($image, $filePath, $ext, 90);

            // 4. Generate thumbnail for admin preview (320x180 max)
            $thumbnailPath = $this->getThumbnailPath($filePath);
            $this->createThumbnailFromResource($image, $thumbnailPath, $ext, 320, 180);

            imagedestroy($image);

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
    protected function saveImage($image, string $path, string $ext, int $quality = 90): bool
    {
        return match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, 8),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => false,
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

        $ratio = min($maxWidth / $srcW, $maxHeight / $srcH);
        $thumbW = max(1, (int) round($srcW * $ratio));
        $thumbH = max(1, (int) round($srcH * $ratio));

        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        $this->preserveTransparency($thumb, $ext);
        imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $thumbW, $thumbH, $srcW, $srcH);

        $saved = $this->saveImage($thumb, $targetPath, $ext, 80);
        imagedestroy($thumb);

        return $saved;
    }

    /**
     * Compute thumbnail file path
     */
    public function getThumbnailPath(string $filePath): string
    {
        $dir = dirname($filePath);
        $filename = basename($filePath);
        return $dir . DIRECTORY_SEPARATOR . 'thumb_' . $filename;
    }
}
