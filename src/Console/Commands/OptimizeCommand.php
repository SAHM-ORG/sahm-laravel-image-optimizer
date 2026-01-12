<?php

namespace SAHM\ImageOptimizer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use SAHM\ImageOptimizer\Services\ImageOptimizationService;
use Symfony\Component\HttpFoundation\File\File;

class OptimizeCommand extends Command
{
    protected $signature = 'image-optimizer:optimize 
                            {path : Directory path to optimize}
                            {--quality= : Quality level (0-100)}
                            {--format= : Output format (webp, avif)}
                            {--preset= : Use preset configuration}
                            {--recursive : Process subdirectories}';

    protected $description = 'Optimize images in a directory';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return self::FAILURE;
        }

        $this->info("Optimizing images in: {$path}");
        $this->newLine();

        $options = array_filter([
            'quality' => $this->option('quality'),
            'format' => $this->option('format'),
            'preset' => $this->option('preset'),
        ]);

        $files = $this->getImageFiles($path, $this->option('recursive'));
        $total = count($files);

        if ($total === 0) {
            $this->warn('No images found');
            return self::SUCCESS;
        }

        $this->info("Found {$total} image(s)");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $optimizer = app(ImageOptimizationService::class);
        $optimized = 0;
        $failed = 0;
        $totalSaved = 0;

        foreach ($files as $file) {
            try {
                $uploadedFile = new UploadedFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    $file->getMimeType(),
                    null,
                    true
                );

                $imageData = $optimizer->optimize($uploadedFile, $options);
                
                $saved = $imageData->metadata['original']['size'] - $imageData->metadata['optimized']['size'];
                $totalSaved += $saved;
                $optimized++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed: {$file->getFilename()} - {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Results
        $this->info("Optimization complete!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total images', $total],
                ['Optimized', $optimized],
                ['Failed', $failed],
                ['Total saved', $this->formatBytes($totalSaved)],
                ['Average reduction', $optimized > 0 ? round($totalSaved / $optimized / 1024, 2) . ' KB' : '0 KB'],
            ]
        );

        return self::SUCCESS;
    }

    private function getImageFiles(string $path, bool $recursive): array
    {
        $extensions = config('image-optimizer.validation.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp']);

        $flags = $recursive ? (\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS) : 0;
        $iterator = $recursive 
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, $flags))
            : new \DirectoryIterator($path);

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $extensions)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
