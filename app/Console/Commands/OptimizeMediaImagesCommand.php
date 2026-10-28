<?php

namespace App\Console\Commands;

use App\Models\MediaItem;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeMediaImagesCommand extends Command
{
    protected $signature = 'media:optimize-images 
                            {--force : Forzar optimización en todas las imágenes aunque ya tengan miniatura}
                            {--thumbnails-only : Solo generar miniaturas JPEG sin redimensionar las imágenes principales}';

    protected $description = 'Comprime imágenes gigantescas a Full HD 1080p y genera miniaturas ultralivianas de 10KB para el panel y Smart TVs';

    public function handle(ImageOptimizerService $optimizer): int
    {
        $this->info('===============================================================');
        $this->info('  VISOR TV - OPTIMIZADOR INTELIGENTE DE IMÁGENES');
        $this->info('===============================================================');
        $this->newLine();

        if (!extension_loaded('gd')) {
            $this->error('[ERROR] La extensión PHP GD no está disponible en este entorno.');
            return Command::FAILURE;
        }

        $mediaDir = storage_path('app/public/media');
        if (!File::isDirectory($mediaDir)) {
            $this->warn("El directorio {$mediaDir} no existe.");
            return Command::SUCCESS;
        }

        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $files = File::files($mediaDir);
        $imageFiles = array_filter($files, function ($f) use ($extensions) {
            $fn = $f->getFilename();
            return in_array(strtolower($f->getExtension()), $extensions)
                && !str_starts_with($fn, 'thumb_')
                && !str_starts_with($fn, 'temp_');
        });

        if (empty($imageFiles)) {
            $this->info('No se encontraron imágenes en el directorio de medios.');
            return Command::SUCCESS;
        }

        $this->info('Se encontraron ' . count($imageFiles) . ' archivo(s) de imagen para procesar.');
        $this->newLine();

        $thumbnailsOnly = $this->option('thumbnails-only');
        $force = $this->option('force');

        $totalOriginalBytes = 0;
        $totalFinalBytes = 0;
        $processedCount = 0;
        $thumbsCreated = 0;

        foreach ($imageFiles as $file) {
            $filePath = $file->getRealPath();
            $filename = $file->getFilename();
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $ext = strtolower($file->getExtension());
            $origBytes = $file->getSize();
            $origFormatted = round($origBytes / (1024 * 1024), 2) . ' MB';

            $thumbJpgPath = $mediaDir . DIRECTORY_SEPARATOR . 'thumb_' . $baseName . '.jpg';
            $hasThumb = file_exists($thumbJpgPath);

            if ($thumbnailsOnly) {
                if (!$hasThumb || $force) {
                    $this->line("Generando miniatura para: {$filename} ({$origFormatted})...");
                    if ($optimizer->createThumbnail($filePath, $thumbJpgPath, 320, 180)) {
                        $thumbSize = round(filesize($thumbJpgPath) / 1024, 1) . ' KB';
                        $this->info("  [OK] Miniatura creada: {$thumbSize}");
                        $thumbsCreated++;
                    } else {
                        $this->warn("  [AVISO] No se pudo generar miniatura para: {$filename}");
                    }
                }
                continue;
            }

            // Full optimization: downscale + compress + thumbnail
            $shouldOptimize = $force || !$hasThumb || ($origBytes > 1.5 * 1024 * 1024);

            if (!$shouldOptimize) {
                $this->line("Saltando {$filename} (ya optimizado o menor a 1.5 MB)");
                continue;
            }

            $this->line("Optimizando imagen: {$filename} ({$origFormatted})...");
            $res = $optimizer->optimize($filePath, $ext);

            if ($res['optimized']) {
                clearstatcache(true, $filePath);
                $newBytes = filesize($filePath);
                $newFormatted = round($newBytes / (1024 * 1024), 2) . ' MB';
                $saved = $origBytes - $newBytes;
                $savedPct = $origBytes > 0 ? round(($saved / $origBytes) * 100, 1) : 0;

                $this->info("  [OK] Optimizado a: {$newFormatted} (Ahorro: {$savedPct}%)");

                // Update DB record
                $mediaItem = MediaItem::where('filename', $filename)->first();
                if ($mediaItem) {
                    $updates = ['file_size' => $newBytes];
                    if (!empty($res['width']) && !empty($res['height'])) {
                        $updates['resolution'] = "{$res['width']}x{$res['height']}";
                    }
                    $mediaItem->update($updates);
                }

                $totalOriginalBytes += $origBytes;
                $totalFinalBytes += $newBytes;
                $processedCount++;
                $thumbsCreated++;
            } else {
                $this->warn("  [AVISO] Omitido: " . ($res['error'] ?? 'Sin cambios'));
            }
        }

        $this->newLine();
        $this->info('===============================================================');
        $this->info('  RESUMEN DE OPTIMIZACIÓN DE IMÁGENES');
        $this->info('===============================================================');
        $this->info("Imágenes procesadas:   {$processedCount}");
        $this->info("Miniaturas generadas:  {$thumbsCreated}");

        if ($totalOriginalBytes > 0 && !$thumbnailsOnly) {
            $totalSavedBytes = $totalOriginalBytes - $totalFinalBytes;
            $savedMB = round($totalSavedBytes / (1024 * 1024), 2);
            $totalOrigMB = round($totalOriginalBytes / (1024 * 1024), 2);
            $totalFinalMB = round($totalFinalBytes / (1024 * 1024), 2);
            $totalPct = round(($totalSavedBytes / $totalOriginalBytes) * 100, 1);

            $this->info("Espacio original:      {$totalOrigMB} MB");
            $this->info("Espacio final:         {$totalFinalMB} MB");
            $this->info("Espacio liberado:      {$savedMB} MB (-{$totalPct}%)");
        }

        return Command::SUCCESS;
    }
}
