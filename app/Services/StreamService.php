<?php

namespace App\Services;

use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamService
{
    /**
     * Stream media file with HTTP 206, ETag 304, HEAD support and Path Traversal protection
     */
    public function streamFile(string $filename, Request $request)
    {
        // 1. Strict Path Traversal Protection
        if (str_contains($filename, '..') || str_contains($filename, '\\') || str_starts_with($filename, '/')) {
            abort(400, 'Nombre de archivo no válido o intento de navegación no permitida');
        }

        // Clean filename
        $cleanFilename = basename($filename);
        $isThumb = $request->boolean('thumb') || $request->input('thumb') === '1';

        // 2. Check if it's an external URL
        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return redirect()->away($filename);
        }

        // Check in database if filename is an external URL
        $mediaByFilename = MediaItem::where('filename', $cleanFilename)
            ->orWhere('filename', $filename)
            ->first();

        if ($mediaByFilename && (str_starts_with($mediaByFilename->filename, 'http://') || str_starts_with($mediaByFilename->filename, 'https://'))) {
            return redirect()->away($mediaByFilename->filename);
        }

        // 3. Locate file on disk
        $disk = Storage::disk('public');
        $targetPath = 'media/' . $cleanFilename;

        if ($isThumb) {
            $thumbPath = 'media/thumb_' . $cleanFilename;
            if ($disk->exists($thumbPath)) {
                $targetPath = $thumbPath;
            }
        }

        if (!$disk->exists($targetPath)) {
            // Fallback: check without media/ prefix
            if ($disk->exists($cleanFilename)) {
                $targetPath = $cleanFilename;
            } else {
                abort(404, 'Archivo multimedia no encontrado en el servidor');
            }
        }

        $fullPath = $disk->path($targetPath);
        $fileSize = (int) @filesize($fullPath);
        $lastModified = (int) @filemtime($fullPath);
        $etag = sprintf('"%x-%x"', $lastModified, $fileSize);

        // Determine MIME Type
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'm4v' => 'video/mp4',
            'ogg' => 'video/ogg',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // 4. Conditional GET / HTTP 304 Not Modified
        $ifNoneMatch = $request->header('If-None-Match');
        $ifModifiedSince = $request->header('If-Modified-Since');

        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            ]);
        }

        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            ]);
        }

        $baseHeaders = [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
        ];

        // 5. Handle HEAD Requests (for Smart TVs instant codec probing)
        if ($request->isMethod('HEAD')) {
            $baseHeaders['Content-Length'] = (string) $fileSize;
            return response('', 200, $baseHeaders);
        }

        // 6. Handle Range Requests (HTTP 206 Partial Content)
        $range = $request->header('Range');
        if ($range && str_starts_with($range, 'bytes=')) {
            $rangeSpec = substr($range, 6);
            $dashPos = strpos($rangeSpec, '-');

            if ($dashPos === false) {
                return response('', 416, ['Content-Range' => "bytes */{$fileSize}"]);
            }

            $startStr = substr($rangeSpec, 0, $dashPos);
            $endStr = substr($rangeSpec, $dashPos + 1);

            $start = ($startStr === '') ? max(0, $fileSize - (int) $endStr) : (int) $startStr;
            $end = ($endStr === '') ? ($fileSize - 1) : min((int) $endStr, $fileSize - 1);

            if ($start > $end || $start >= $fileSize) {
                return response('', 416, ['Content-Range' => "bytes */{$fileSize}"]);
            }

            $length = $end - $start + 1;

            $headers = array_merge($baseHeaders, [
                'Content-Length' => (string) $length,
                'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            ]);

            return new StreamedResponse(function () use ($fullPath, $start, $length) {
                $handle = @fopen($fullPath, 'rb');
                if (!$handle) {
                    return;
                }
                if ($start > 0) {
                    fseek($handle, $start);
                }

                $remaining = $length;
                $chunkSize = 512 * 1024; // 512 KB high-performance chunks

                while ($remaining > 0 && !feof($handle)) {
                    $readSize = min($remaining, $chunkSize);
                    $data = fread($handle, $readSize);
                    if ($data === false) {
                        break;
                    }
                    echo $data;
                    flush();
                    $remaining -= strlen($data);
                }
                fclose($handle);
            }, 206, $headers);
        }

        // 7. Full File Stream
        $baseHeaders['Content-Length'] = (string) $fileSize;
        return new StreamedResponse(function () use ($fullPath, $fileSize) {
            $handle = @fopen($fullPath, 'rb');
            if (!$handle) {
                return;
            }

            $chunkSize = 512 * 1024;
            while (!feof($handle)) {
                $data = fread($handle, $chunkSize);
                if ($data === false) {
                    break;
                }
                echo $data;
                flush();
            }
            fclose($handle);
        }, 200, $baseHeaders);
    }
}
