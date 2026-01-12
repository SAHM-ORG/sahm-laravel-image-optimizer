<?php

namespace SAHM\ImageOptimizer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupCommand extends Command
{
    protected $signature = 'image-optimizer:cleanup
                            {--days=30 : Delete images older than X days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old optimized images';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up images older than {$days} days...");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            $this->newLine();
        }

        $disk = config('image-optimizer.storage.disk');
        $basePath = config('image-optimizer.storage.base_path') . '/' . config('image-optimizer.storage.paths.optimized');

        $cutoffDate = Carbon::now()->subDays($days);
        $deleted = 0;
        $totalSize = 0;

        $directories = Storage::disk($disk)->directories($basePath);

        foreach ($directories as $dir) {
            $metaFile = $dir . '/meta.json';

            if (!Storage::disk($disk)->exists($metaFile)) {
                continue;
            }

            $lastModified = Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($metaFile));

            if ($lastModified->lt($cutoffDate)) {
                $size = $this->getDirectorySize($disk, $dir);
                $totalSize += $size;

                $this->line("Found: {$dir} (modified: {$lastModified->diffForHumans()}, size: {$this->formatBytes($size)})");

                if (!$dryRun) {
                    Storage::disk($disk)->deleteDirectory($dir);
                    $deleted++;
                }
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Would delete {$deleted} image(s) ({$this->formatBytes($totalSize)})");
        } else {
            $this->info("Deleted {$deleted} image(s) ({$this->formatBytes($totalSize)})");
        }

        return self::SUCCESS;
    }

    private function getDirectorySize(string $disk, string $path): int
    {
        $size = 0;
        $files = Storage::disk($disk)->allFiles($path);

        foreach ($files as $file) {
            $size += Storage::disk($disk)->size($file);
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
