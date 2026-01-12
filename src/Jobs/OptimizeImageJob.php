<?php

namespace SAHM\ImageOptimizer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use SAHM\ImageOptimizer\Facades\ImageOptimizer;

class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $filePath,
        private string $originalName,
        private array $options = []
    ) {
        $this->onConnection(config('image-optimizer.queue.connection'));
        $this->onQueue(config('image-optimizer.queue.queue', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            throw new \Exception("File not found: {$this->filePath}");
        }

        // Create UploadedFile instance
        $uploadedFile = new UploadedFile(
            $this->filePath,
            $this->originalName,
            mime_content_type($this->filePath),
            null,
            true
        );

        // Optimize
        ImageOptimizer::optimize($uploadedFile, $this->options);

        // Clean up temporary file
        @unlink($this->filePath);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Clean up temporary file on failure
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }

        // Log the failure
        logger()->error('Image optimization job failed', [
            'file' => $this->originalName,
            'error' => $exception->getMessage(),
        ]);
    }
}
