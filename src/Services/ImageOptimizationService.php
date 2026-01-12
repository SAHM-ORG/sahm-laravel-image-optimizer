<?php

namespace SAHM\ImageOptimizer\Services;

use Illuminate\Http\UploadedFile;
use SAHM\ImageOptimizer\DTOs\ImageData;
use SAHM\ImageOptimizer\Processors\ProcessorManager;
use SAHM\ImageOptimizer\Exceptions\ImageOptimizationException;
use SAHM\ImageOptimizer\Exceptions\InvalidImageException;

class ImageOptimizationService
{
    public function __construct(
        private ImageStorageService $storage,
        private ProcessorManager $processorManager,
        private ImageCacheService $cache,
        private array $config
    ) {}

    /**
     * Optimize uploaded image
     */
    public function optimize(UploadedFile $file, array $options = []): ImageData
    {
        // Validate
        $this->validateImage($file);

        // Generate hash
        $hash = $this->storage->generateHash($file);

        // Check cache
        $cacheKey = "metadata:{$hash}";
        if ($cached = $this->cache->get($cacheKey)) {
            return ImageData::fromArray($cached);
        }

        // Check if already exists
        if ($this->storage->exists($hash)) {
            $metadata = $this->storage->getMetadata($hash);
            if ($metadata) {
                $imageData = ImageData::fromMetadata($hash, $metadata);
                $this->cache->put($cacheKey, $imageData->toArray());
                return $imageData;
            }
        }

        // Merge with preset if specified
        if (isset($options['preset'])) {
            $options = $this->mergePreset($options['preset'], $options);
        }

        // Store original
        $originalPath = $this->storage->storeOriginal($file, $hash);
        $originalFullPath = $this->storage->getFullPath($originalPath);

        // Get processor
        $processor = $this->processorManager->getProcessor();

        // Get image info
        $info = $processor->getImageInfo($originalFullPath);

        // Limit dimensions if needed
        $info = $this->limitDimensions($info, $options);

        // Get output format
        $format = $options['format'] ?? $this->config['formats']['output'] ?? 'webp';

        // Optimize main image
        $optimizedPath = $this->storage->getOptimizedPath($hash, $file->getClientOriginalName(), null, $format);
        $optimizedFullPath = $this->storage->getFullPath($optimizedPath);

        $this->ensureDirectory($optimizedFullPath);

        // Process based on dimensions
        if ($info['needs_resize']) {
            $processor->resize(
                $originalFullPath,
                $optimizedFullPath,
                $info['width'],
                $info['height'],
                ['quality' => $options['quality'] ?? $this->config['processing']['default_quality']]
            );
        } else {
            copy($originalFullPath, $optimizedFullPath);
        }

        // Convert to target format
        if ($format === 'webp') {
            $processor->convertToWebP(
                $optimizedFullPath,
                $optimizedFullPath,
                $options['quality'] ?? $this->config['processing']['default_quality']
            );
        } elseif ($format === 'avif') {
            $processor->convertToAvif(
                $optimizedFullPath,
                $optimizedFullPath,
                $options['quality'] ?? $this->config['processing']['default_quality']
            );
        }

        // Generate variants
        $variants = $this->generateVariants($optimizedFullPath, $hash, $file->getClientOriginalName(), $format, $options);

        // Generate blur placeholder
        $blurPlaceholder = null;
        if ($this->shouldGenerateBlur($options)) {
            $blurPlaceholder = $processor->generateBlurPlaceholder(
                $optimizedFullPath,
                $this->config['processing']['blur_placeholder']['width'] ?? 20,
                $this->config['processing']['blur_placeholder']['quality'] ?? 30
            );
        }

        // Build metadata
        $metadata = $this->buildMetadata(
            $file,
            $originalPath,
            $optimizedPath,
            $variants,
            $info,
            $blurPlaceholder,
            $options
        );

        // Save metadata
        $this->storage->saveMetadata($hash, $metadata);

        // Create ImageData
        $imageData = ImageData::fromMetadata($hash, $metadata);

        // Cache
        $this->cache->put($cacheKey, $imageData->toArray());

        return $imageData;
    }

    /**
     * Get optimized image data by hash
     */
    public function get(string $hash): ?ImageData
    {
        // Try cache
        $cacheKey = "metadata:{$hash}";
        if ($cached = $this->cache->get($cacheKey)) {
            return ImageData::fromArray($cached);
        }

        // Load from storage
        $metadata = $this->storage->getMetadata($hash);

        if (!$metadata) {
            return null;
        }

        $imageData = ImageData::fromMetadata($hash, $metadata);

        // Cache
        $this->cache->put($cacheKey, $imageData->toArray());

        return $imageData;
    }

    /**
     * Delete optimized image
     */
    public function delete(string $hash): bool
    {
        // Clear cache
        $this->cache->forget("metadata:{$hash}");

        // Delete files
        return $this->storage->delete($hash);
    }

    /**
     * Get supported formats
     */
    public function getSupportedFormats(): array
    {
        return $this->processorManager->getProcessor()->getSupportedFormats();
    }

    /**
     * Check if processor is available
     */
    public function hasProcessor(string $name): bool
    {
        return $this->processorManager->hasProcessor($name);
    }

    /**
     * Get active processor name
     */
    public function getActiveProcessor(): string
    {
        return $this->processorManager->getProcessor()->getName();
    }

    /**
     * Get processor info
     */
    public function getProcessorInfo(): array
    {
        return $this->processorManager->getInfo();
    }

    /**
     * Get statistics
     */
    public function getStats(): array
    {
        // This is a basic implementation
        // In production, you might want to track stats in a separate file/cache
        return [
            'processor' => $this->getActiveProcessor(),
            'supported_formats' => $this->getSupportedFormats(),
            'config' => [
                'default_quality' => $this->config['processing']['default_quality'],
                'sizes' => $this->config['processing']['sizes'],
                'output_format' => $this->config['formats']['output'],
            ],
        ];
    }

    /**
     * Validate uploaded image
     */
    private function validateImage(UploadedFile $file): void
    {
        $validation = $this->config['validation'];

        // Check file size
        if ($file->getSize() > ($validation['max_file_size'] * 1024)) {
            throw new InvalidImageException('File size exceeds maximum allowed size');
        }

        // Check MIME type
        $mime = $file->getMimeType();
        if (!in_array($mime, $validation['allowed_mimes'])) {
            throw new InvalidImageException('Invalid image type');
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $validation['allowed_extensions'])) {
            throw new InvalidImageException('Invalid file extension');
        }

        // Verify actual image type
        if ($validation['verify_image_type'] ?? true) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                throw new InvalidImageException('File is not a valid image');
            }

            // Check minimum dimensions
            if ($imageInfo[0] < $validation['min_width'] || $imageInfo[1] < $validation['min_height']) {
                throw new InvalidImageException('Image dimensions are too small');
            }
        }
    }

    /**
     * Limit image dimensions
     */
    private function limitDimensions(array $info, array $options): array
    {
        $maxWidth = $options['max_width'] ?? $this->config['processing']['max_width'];
        $maxHeight = $options['max_height'] ?? $this->config['processing']['max_height'];

        $needsResize = false;
        $width = $info['width'];
        $height = $info['height'];

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $width = (int) ($width * $ratio);
            $height = (int) ($height * $ratio);
            $needsResize = true;
        }

        return array_merge($info, [
            'width' => $width,
            'height' => $height,
            'needs_resize' => $needsResize,
        ]);
    }

    /**
     * Generate responsive variants
     */
    private function generateVariants(string $sourcePath, string $hash, string $filename, string $format, array $options): array
    {
        $variants = [];
        $sizes = $options['sizes'] ?? $this->config['processing']['sizes'];
        $qualities = $this->config['processing']['qualities'];

        $processor = $this->processorManager->getProcessor();
        $sourceInfo = $processor->getImageInfo($sourcePath);

        foreach ($sizes as $width) {
            // Skip if variant is larger than source
            if ($width >= $sourceInfo['width']) {
                continue;
            }

            $quality = $qualities[$width] ?? $this->config['processing']['default_quality'];
            $variantPath = $this->storage->getOptimizedPath($hash, $filename, "{$width}w", $format);
            $variantFullPath = $this->storage->getFullPath($variantPath);

            $this->ensureDirectory($variantFullPath);

            // Resize
            $processor->resize($sourcePath, $variantFullPath, $width, null, ['quality' => $quality]);

            $variants["{$width}w"] = [
                'path' => $variantPath,
                'url' => $this->storage->getUrl($variantPath),
                'size' => $this->storage->getSize($variantPath),
                'width' => $width,
                'quality' => $quality,
            ];
        }

        return $variants;
    }

    /**
     * Build metadata array
     */
    private function buildMetadata(
        UploadedFile $file,
        string $originalPath,
        string $optimizedPath,
        array $variants,
        array $info,
        ?string $blurPlaceholder,
        array $options
    ): array {
        $originalSize = $this->storage->getSize($originalPath);
        $optimizedSize = $this->storage->getSize($optimizedPath);

        return [
            'original' => [
                'filename' => $file->getClientOriginalName(),
                'path' => $originalPath,
                'size' => $originalSize,
                'format' => $info['format'],
                'width' => $info['width'],
                'height' => $info['height'],
                'uploaded_at' => now()->toIso8601String(),
            ],
            'optimized' => [
                'format' => $options['format'] ?? $this->config['formats']['output'],
                'path' => $optimizedPath,
                'url' => $this->storage->getUrl($optimizedPath),
                'size' => $optimizedSize,
                'compression_ratio' => $originalSize > 0 ? round((1 - $optimizedSize / $originalSize) * 100, 2) : 0,
            ],
            'variants' => $variants,
            'srcset' => $this->buildSrcset($optimizedPath, $variants),
            'sizes' => $this->getSizesAttribute($options),
            'blur_placeholder' => $blurPlaceholder,
            'is_lcp' => $options['is_lcp'] ?? false,
            'alt' => $options['alt'] ?? '',
            'processing' => [
                'processor' => $this->getActiveProcessor(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Build srcset string
     */
    private function buildSrcset(string $mainPath, array $variants): string
    {
        $srcset = [];

        // Add variants
        foreach ($variants as $descriptor => $variant) {
            $srcset[] = "{$variant['url']} {$descriptor}";
        }

        // Add main image as largest
        if (!empty($srcset)) {
            $srcset[] = $this->storage->getUrl($mainPath);
        }

        return implode(', ', $srcset);
    }

    /**
     * Get sizes attribute
     */
    private function getSizesAttribute(array $options): string
    {
        if (isset($options['sizes_attr'])) {
            return $options['sizes_attr'];
        }

        if (isset($options['sizes_preset'])) {
            $presets = $this->config['lighthouse']['sizes_presets'] ?? [];
            return $presets[$options['sizes_preset']] ?? '100vw';
        }

        return $this->config['lighthouse']['default_sizes'] ?? '100vw';
    }

    /**
     * Check if should generate blur placeholder
     */
    private function shouldGenerateBlur(array $options): bool
    {
        if (isset($options['blur'])) {
            return (bool) $options['blur'];
        }

        return $this->config['processing']['blur_placeholder']['enabled'] ?? true;
    }

    /**
     * Merge preset configuration
     */
    private function mergePreset(string $preset, array $options): array
    {
        $presets = $this->config['presets'] ?? [];

        if (!isset($presets[$preset])) {
            return $options;
        }

        return array_merge($presets[$preset], $options);
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectory(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
