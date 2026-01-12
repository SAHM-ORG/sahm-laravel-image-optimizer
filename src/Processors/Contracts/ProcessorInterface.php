<?php

namespace SAHM\ImageOptimizer\Processors\Contracts;

interface ProcessorInterface
{
    /**
     * Check if processor is available
     */
    public function isAvailable(): bool;

    /**
     * Get processor name
     */
    public function getName(): string;

    /**
     * Get image information
     */
    public function getImageInfo(string $path): array;

    /**
     * Optimize image
     */
    public function optimize(string $sourcePath, string $destinationPath, array $options = []): bool;

    /**
     * Resize image
     */
    public function resize(string $sourcePath, string $destinationPath, int $width, ?int $height = null, array $options = []): bool;

    /**
     * Convert to WebP
     */
    public function convertToWebP(string $sourcePath, string $destinationPath, int $quality = 85): bool;

    /**
     * Convert to AVIF
     */
    public function convertToAvif(string $sourcePath, string $destinationPath, int $quality = 85): bool;

    /**
     * Generate blur placeholder
     */
    public function generateBlurPlaceholder(string $sourcePath, int $width = 20, int $quality = 30): ?string;

    /**
     * Strip metadata
     */
    public function stripMetadata(string $path): bool;

    /**
     * Auto-orient image based on EXIF
     */
    public function autoOrient(string $path): bool;

    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array;
}
