<?php

namespace SAHM\ImageOptimizer\Processors;

use SAHM\ImageOptimizer\Processors\Contracts\ProcessorInterface;
use SAHM\ImageOptimizer\Exceptions\ImageOptimizationException;

class GdProcessor implements ProcessorInterface
{
    public function isAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    public function getName(): string
    {
        return 'gd';
    }

    public function getImageInfo(string $path): array
    {
        $info = @getimagesize($path);
        
        if ($info === false) {
            throw new ImageOptimizationException("Failed to get image info");
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'format' => $this->getFormatFromMime($info['mime']),
            'mime' => $info['mime'],
            'size' => filesize($path),
            'has_alpha' => $this->hasAlphaChannel($path, $info['mime']),
        ];
    }

    public function optimize(string $sourcePath, string $destinationPath, array $options = []): bool
    {
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new ImageOptimizationException("Failed to load image");
        }

        // Load image
        $image = $this->createImageFromFile($sourcePath, $info['mime']);
        if ($image === false) {
            throw new ImageOptimizationException("Failed to create image resource");
        }

        // Auto-orient (limited support in GD)
        if ($options['auto_orient'] ?? true) {
            $image = $this->autoOrientImage($image, $sourcePath);
        }

        // Save optimized
        $quality = $options['quality'] ?? 85;
        $result = $this->saveImage($image, $destinationPath, $info['mime'], $quality);

        imagedestroy($image);

        return $result;
    }

    public function resize(string $sourcePath, string $destinationPath, int $width, ?int $height = null, array $options = []): bool
    {
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new ImageOptimizationException("Failed to load image");
        }

        $source = $this->createImageFromFile($sourcePath, $info['mime']);
        if ($source === false) {
            throw new ImageOptimizationException("Failed to create image resource");
        }

        // Calculate height maintaining aspect ratio
        if ($height === null) {
            $height = (int) ($info[1] * ($width / $info[0]));
        }

        // Create new image
        $resized = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        if ($this->hasAlphaChannel($sourcePath, $info['mime'])) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
        }

        // Resize with high quality
        imagecopyresampled(
            $resized,
            $source,
            0, 0, 0, 0,
            $width,
            $height,
            $info[0],
            $info[1]
        );

        // Save
        $quality = $options['quality'] ?? 85;
        $result = $this->saveImage($resized, $destinationPath, $info['mime'], $quality);

        imagedestroy($source);
        imagedestroy($resized);

        return $result;
    }

    public function convertToWebP(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        if (!function_exists('imagewebp')) {
            throw new ImageOptimizationException("WebP is not supported by this GD installation");
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new ImageOptimizationException("Failed to load image");
        }

        $image = $this->createImageFromFile($sourcePath, $info['mime']);
        if ($image === false) {
            throw new ImageOptimizationException("Failed to create image resource");
        }

        // Preserve transparency
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Save as WebP
        $result = imagewebp($image, $destinationPath, $quality);

        imagedestroy($image);

        return $result;
    }

    public function convertToAvif(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        if (!function_exists('imageavif')) {
            throw new ImageOptimizationException("AVIF is not supported by this GD installation");
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new ImageOptimizationException("Failed to load image");
        }

        $image = $this->createImageFromFile($sourcePath, $info['mime']);
        if ($image === false) {
            throw new ImageOptimizationException("Failed to create image resource");
        }

        // Save as AVIF
        $result = imageavif($image, $destinationPath, $quality);

        imagedestroy($image);

        return $result;
    }

    public function generateBlurPlaceholder(string $sourcePath, int $width = 20, int $quality = 30): ?string
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        $source = $this->createImageFromFile($sourcePath, $info['mime']);
        if ($source === false) {
            return null;
        }

        // Calculate height
        $height = (int) ($info[1] * ($width / $info[0]));

        // Resize
        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

        // Apply blur filter
        for ($i = 0; $i < 3; $i++) {
            imagefilter($thumb, IMG_FILTER_GAUSSIAN_BLUR);
        }

        // Get as WebP base64
        ob_start();
        imagewebp($thumb, null, $quality);
        $blob = ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumb);

        return 'data:image/webp;base64,' . base64_encode($blob);
    }

    public function stripMetadata(string $path): bool
    {
        // GD automatically strips most metadata
        return true;
    }

    public function autoOrient(string $path): bool
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }

        $exif = @exif_read_data($path);
        if (!$exif || !isset($exif['Orientation'])) {
            return true;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }

        $image = $this->createImageFromFile($path, $info['mime']);
        if ($image === false) {
            return false;
        }

        $image = $this->autoOrientImage($image, $path);
        $result = $this->saveImage($image, $path, $info['mime'], 90);

        imagedestroy($image);

        return $result;
    }

    public function getSupportedFormats(): array
    {
        $formats = [];
        
        if (function_exists('imagewebp')) {
            $formats[] = 'webp';
        }
        
        if (function_exists('imageavif')) {
            $formats[] = 'avif';
        }

        return $formats;
    }

    /**
     * Create GD image resource from file
     */
    private function createImageFromFile(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    /**
     * Save GD image resource to file
     */
    private function saveImage($image, string $path, string $originalMime, int $quality): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, (int) (9 - ($quality / 11))),
            'gif' => imagegif($image, $path),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            'avif' => function_exists('imageavif') ? imageavif($image, $path, $quality) : false,
            default => false,
        };
    }

    /**
     * Auto-orient image based on EXIF
     */
    private function autoOrientImage($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        if (!$exif || !isset($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                return imagerotate($image, 180, 0);
            case 6:
                return imagerotate($image, -90, 0);
            case 8:
                return imagerotate($image, 90, 0);
            default:
                return $image;
        }
    }

    /**
     * Check if image has alpha channel
     */
    private function hasAlphaChannel(string $path, string $mime): bool
    {
        return in_array($mime, ['image/png', 'image/webp', 'image/gif']);
    }

    /**
     * Get format from MIME type
     */
    private function getFormatFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => 'unknown',
        };
    }
}
