<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Stream media file with HTTP 206 Partial Content (Range requests) and ETag caching.
     * Ultra-low memory usage, ideal for 1080p/4K TV playback and 24/7 loops.
     */
    public function stream(Request $request, string $filename)
    {
        // Decode filename in case of special characters
        $filename = rawurldecode($filename);

        // Security check: prevent directory traversal attacks
        if (str_contains($filename, '..') || str_contains($filename, '\\')) {
            abort(400, 'Invalid filename');
        }

        // Handle external URLs
        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return redirect()->away($filename);
        }

        if (str_contains($filename, '/')) {
            abort(400, 'Invalid filename');
        }

        $isThumb = $request->query('thumb') === '1' || str_starts_with($filename, 'thumb_');
        $checkFilename = $filename;
        if ($isThumb && !str_starts_with($filename, 'thumb_')) {
            $checkFilename = 'thumb_' . $filename;
        }

        // Possible storage locations
        $paths = [
            Storage::disk('public')->path('media/' . $checkFilename),
            public_path('uploads/media/' . $checkFilename),
            public_path('storage/media/' . $checkFilename),
            base_path('storage/app/public/media/' . $checkFilename),
        ];

        // Fallback to non-thumb if thumb is missing
        if ($isThumb) {
            $paths[] = Storage::disk('public')->path('media/' . $filename);
            $paths[] = public_path('uploads/media/' . $filename);
            $paths[] = public_path('storage/media/' . $filename);
            $paths[] = base_path('storage/app/public/media/' . $filename);
        }

        $filePath = null;
        foreach ($paths as $path) {
            if (file_exists($path) && is_file($path)) {
                $filePath = $path;
                break;
            }
        }

        // If not found locally, check if it's an external URL stored in DB
        if (!$filePath) {
            $media = MediaItem::where('filename', $filename)->first();
            if ($media && (str_starts_with($media->filename, 'http://') || str_starts_with($media->filename, 'https://'))) {
                return redirect()->away($media->filename);
            }
            abort(404, 'Archivo multimedia no encontrado');
        }

        $fileSize = filesize($filePath);
        $mimeType = File::mimeType($filePath) ?: 'application/octet-stream';

        // Fix mime type for specific extensions
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        if (isset($mimeTypes[$ext])) {
            $mimeType = $mimeTypes[$ext];
        }

        $etag = '"' . md5($filePath . filemtime($filePath) . $fileSize) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT';

        // 304 Not Modified check for high-speed client caching
        if ($request->header('If-None-Match') === $etag || $request->header('If-Modified-Since') === $lastModified) {
            return response('', 304, [
                'ETag' => $etag,
                'Last-Modified' => $lastModified,
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        // For non-video files (images, svg), send cached response or HEAD response
        if (!str_starts_with($mimeType, 'video/')) {
            $imageHeaders = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'ETag' => $etag,
                'Last-Modified' => $lastModified,
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'Access-Control-Allow-Origin' => '*',
                'Accept-Ranges' => 'bytes',
            ];

            if ($request->isMethod('HEAD')) {
                return response('', 200, $imageHeaders);
            }

            return response()->file($filePath, $imageHeaders);
        }

        // Handle HTTP Range Requests for video
        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->header('Range');
        if ($rangeHeader && preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $rangeHeader, $matches)) {
            $start = intval($matches[1]);
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
            $status = 206; // Partial Content
        }

        if ($start > $end || $start >= $fileSize) {
            return response('', 416, [
                'Content-Range' => "bytes */$fileSize",
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges, ETag, Last-Modified',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'ETag' => $etag,
            'Last-Modified' => $lastModified,
        ];

        if ($status === 206) {
            $headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $fileSize);
        }

        // HEAD request returns headers immediately without buffer
        if ($request->isMethod('HEAD')) {
            return response('', $status, $headers);
        }

        return new StreamedResponse(function () use ($filePath, $start, $length) {
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                return;
            }

            fseek($handle, $start);
            $remaining = $length;
            $chunkSize = 1024 * 1024; // 1MB buffer chunk for high-speed high-definition TV streaming

            while (!feof($handle) && $remaining > 0 && connection_status() === CONNECTION_NORMAL) {
                $bytesToRead = min($chunkSize, $remaining);
                $buffer = fread($handle, $bytesToRead);
                echo $buffer;
                flush();
                $remaining -= strlen($buffer);
            }

            fclose($handle);
        }, $status, $headers);
    }
}
