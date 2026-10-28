<?php

namespace App\Console\Commands;

use App\Models\MediaItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class OptimizeMediaVideosCommand extends Command
{
    protected $signature = 'media:optimize-videos 
                            {--crf=23 : Factor de calidad CRF (18-28, por defecto 23)}
                            {--force : Forzar optimizacion en todos los videos}
                            {--thumbnails-only : Solo generar miniaturas JPEG para los videos}';

    protected $description = 'Optimiza y comprime videos a 1080p H.264 con FastStart y genera miniaturas ultralivianas de 8KB para Smart TVs';

    public function handle(): int
    {
        $this->info('===============================================================');
        $this->info('  VISOR TV - OPTIMIZADOR INTELIGENTE DE VIDEOS PARA SMART TV');
        $this->info('===============================================================');
        $this->newLine();

        // 1. Verificar disponibilidad de FFmpeg
        $ffmpegBinary = self::findFFmpeg();
        if (!$ffmpegBinary) {
            $this->error('[ERROR] FFmpeg no esta disponible en el PATH del sistema.');
            $this->line('Para instalarlo en Windows, ejecute en PowerShell:');
            $this->line('   winget install Gyan.FFmpeg --accept-package-agreements --accept-source-agreements');
            return Command::FAILURE;
        }

        $this->info("FFmpeg detectado: {$ffmpegBinary}");

        // 2. Localizar directorio de medios
        $mediaDir = storage_path('app/public/media');
        if (!File::isDirectory($mediaDir)) {
            $this->warn("El directorio {$mediaDir} no existe.");
            return Command::SUCCESS;
        }

        $extensions = ['mp4', 'mov', 'webm', 'mkv', 'avi'];
        $files = File::files($mediaDir);
        $videoFiles = array_filter($files, function ($f) use ($extensions) {
            $fn = $f->getFilename();
            return in_array(strtolower($f->getExtension()), $extensions) 
                && !str_starts_with($fn, 'temp_opt_')
                && !str_starts_with($fn, 'thumb_');
        });

        if (empty($videoFiles)) {
            $this->info('No se encontraron videos para procesar en almacenamiento.');
            return Command::SUCCESS;
        }

        $this->info('Se encontraron ' . count($videoFiles) . ' archivo(s) de video.');

        // Si solo se solicitan miniaturas
        if ($this->option('thumbnails-only')) {
            $this->info('Modo: Generacion de miniaturas JPEG livianas (8KB)...');
            $countThumbs = 0;
            foreach ($videoFiles as $video) {
                $filePath = $video->getRealPath();
                $filename = $video->getFilename();
                $thumbPath = $mediaDir . DIRECTORY_SEPARATOR . 'thumb_' . $filename . '.jpg';

                if (file_exists($thumbPath) && !$this->option('force')) {
                    continue;
                }

                $cmd = sprintf(
                    '"%s" -ss 00:00:01 -i "%s" -vframes 1 -vf "scale=320:-1" -q:v 3 "%s" -y 2>&1',
                    $ffmpegBinary,
                    $filePath,
                    $thumbPath
                );
                @exec($cmd);

                if (file_exists($thumbPath)) {
                    $countThumbs++;
                    $this->line("  [THUMB] Creada miniatura para: {$filename} (" . $this->formatBytes(filesize($thumbPath)) . ")");
                }
            }
            $this->info("Miniaturas procesadas exitosamente: {$countThumbs} generadas.");
            return Command::SUCCESS;
        }

        $crf = (int) $this->option('crf');
        if ($crf < 18 || $crf > 32) {
            $crf = 23;
        }

        $totalOriginal = 0;
        $totalOptimized = 0;

        foreach ($videoFiles as $file) {
            $originalPath = $file->getRealPath();
            $filename = $file->getFilename();
            $originalSize = $file->getSize();
            $totalOriginal += $originalSize;

            $this->line("Procesando: <comment>{$filename}</comment> (" . $this->formatBytes($originalSize) . ")...");

            // Nombre temporal de salida
            $tempOutput = $mediaDir . DIRECTORY_SEPARATOR . 'temp_opt_' . $filename;
            if (File::exists($tempOutput)) {
                @unlink($tempOutput);
            }

            // Comando FFmpeg: 1080p max, H.264, FastStart (moov atom al inicio), AAC audio
            $cmd = sprintf(
                '"%s" -y -i "%s" -c:v libx264 -crf %d -preset fast -vf "scale=\'min(1920,iw)\':-2" -c:a aac -b:a 128k -movflags +faststart "%s" 2>&1',
                $ffmpegBinary,
                $originalPath,
                $crf,
                $tempOutput
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($tempOutput)) {
                $newSize = filesize($tempOutput);

                if ($newSize > 0 && ($newSize < $originalSize || $this->option('force'))) {
                    $savedPercent = round((1 - ($newSize / $originalSize)) * 100, 1);
                    @unlink($originalPath);
                    rename($tempOutput, $originalPath);

                    $this->info("  [OK] Reducido de " . $this->formatBytes($originalSize) . " a " . $this->formatBytes($newSize) . " ({$savedPercent}% de ahorro)");

                    // Actualizar registro en base de datos si existe
                    $mediaItem = MediaItem::where('filename', $filename)->first();
                    if ($mediaItem) {
                        $mediaItem->file_size = $newSize;
                        $mediaItem->save();
                    }

                    $totalOptimized += $newSize;
                } else {
                    $this->line("  [INFO] El archivo ya se encuentra en su tamano optimo. Conservando original.");
                    @unlink($tempOutput);
                    $totalOptimized += $originalSize;
                }
            } else {
                $this->warn("  [AVISO] FFmpeg no pudo procesar {$filename}. Conservando archivo.");
                if (File::exists($tempOutput)) {
                    @unlink($tempOutput);
                }
                $totalOptimized += $originalSize;
            }

            // Asegurar que exista miniatura JPEG ultraliviana (8 KB)
            $thumbPath = $mediaDir . DIRECTORY_SEPARATOR . 'thumb_' . $filename . '.jpg';
            if (!file_exists($thumbPath) || $this->option('force')) {
                $thumbCmd = sprintf(
                    '"%s" -ss 00:00:01 -i "%s" -vframes 1 -vf "scale=320:-1" -q:v 3 "%s" -y 2>&1',
                    $ffmpegBinary,
                    $originalPath,
                    $thumbPath
                );
                @exec($thumbCmd);
            }
        }

        $this->newLine();
        $this->info('===============================================================');
        $this->info('  OPTIMIZACION FINALIZADA CON EXITO');
        $this->info('  Tamano Original Total:  ' . $this->formatBytes($totalOriginal));
        $this->info('  Tamano Optimizado Total: ' . $this->formatBytes($totalOptimized));
        $savedTotal = $totalOriginal - $totalOptimized;
        if ($savedTotal > 0) {
            $this->info('  Ahorro Total de Ancho de Banda: ' . $this->formatBytes($savedTotal) . ' (' . round(($savedTotal / $totalOriginal) * 100, 1) . '%)');
        }
        $this->info('===============================================================');

        return Command::SUCCESS;
    }

    public static function findFFmpeg(): ?string
    {
        // 1. En PATH
        $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        foreach ($paths as $dir) {
            $candidate = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'ffmpeg.exe' : 'ffmpeg');
            if (file_exists($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        // 2. Ubicaciones comunes de Winget en Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $localAppData = getenv('LOCALAPPDATA');
            if ($localAppData) {
                $wingetLinks = glob($localAppData . '\\Microsoft\\WinGet\\Links\\ffmpeg.exe');
                if (!empty($wingetLinks) && file_exists($wingetLinks[0])) {
                    return $wingetLinks[0];
                }

                $packageDirs = glob($localAppData . '\\Microsoft\\WinGet\\Packages\\*Gyan*\\*\\bin\\ffmpeg.exe');
                if (!empty($packageDirs) && file_exists($packageDirs[0])) {
                    return $packageDirs[0];
                }
            }
        }

        return null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min((int)$pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
