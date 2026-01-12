<?php

namespace SAHM\ImageOptimizer\Processors;

use Imagick;
use ImagickException;
use SAHM\ImageOptimizer\Processors\Contracts\ProcessorInterface;
use SAHM\ImageOptimizer\Exceptions\ImageOptimizationException;

class ImagickProcessor implements ProcessorInterface
{
    public function isAvailable(): bool
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    public function getName(): string
    {
        return 'imagick';
    }

    public function getImageInfo(string $path): array
    {
        try {
            $image = new Imagick($path);
            
            return [
                'width' => $image->getImageWidth(),
                'height' => $image->getImageHeight(),
                'format' => strtolower($image->getImageFormat()),
                'mime' => $image->getImageMimeType(),
                'size' => filesize($path),
                'has_alpha' => $image->getImageAlphaChannel() !== Imagick::ALPHACHANNEL_UNDEFINED,
            ];
        } catch (ImagickException $e) {
            throw new ImageOptimizationException("Failed to get image info: {$e->getMessage()}");
        }
    }

    public function optimize(string $sourcePath, string $destinationPath, array $options = []): bool
    {
        try {
            $image = new Imagick($sourcePath);
            
            // Auto-orient
            if ($options['auto_orient'] ?? true) {
                $this->autoOrientImage($image);
            }

            // Strip metadata
            if ($options['strip_metadata'] ?? true) {
                $image->stripImage();
            }

            // Set quality
            $quality = $options['quality'] ?? 85;
            $image->setImageCompressionQuality($quality);

            // Progressive/Interlaced
            if ($options['progressive'] ?? true) {
                $image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            }

            // Optimize for web
            $this->optimizeForWeb($image);

            // Save
            $image->writeImage($destinationPath);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            throw new ImageOptimizationException("Failed to optimize image: {$e->getMessage()}");
        }
    }

    public function resize(string $sourcePath, string $destinationPath, int $width, ?int $height = null, array $options = []): bool
    {
        try {
            $image = new Imagick($sourcePath);
            
            // Calculate height maintaining aspect ratio
            if ($height === null) {
                $height = (int) ($image->getImageHeight() * ($width / $image->getImageWidth()));
            }

            // Resize with best quality filter
            $image->resizeImage(
                $width,
                $height,
                Imagick::FILTER_LANCZOS,
                1,
                $options['bestfit'] ?? true
            );

            // Apply optimization
            $this->optimizeForWeb($image);

            // Set quality
            $quality = $options['quality'] ?? 85;
            $image->setImageCompressionQuality($quality);

            // Save
            $image->writeImage($destinationPath);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            throw new ImageOptimizationException("Failed to resize image: {$e->getMessage()}");
        }
    }

    public function convertToWebP(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        try {
            $image = new Imagick($sourcePath);
            
            // Set format to WebP
            $image->setImageFormat('webp');
            
            // WebP options
            $image->setOption('webp:method', '6'); // Best compression
            $image->setOption('webp:lossless', 'false');
            $image->setOption('webp:alpha-quality', '100');
            $image->setImageCompressionQuality($quality);

            // Optimize
            $this->optimizeForWeb($image);

            // Save
            $image->writeImage($destinationPath);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            throw new ImageOptimizationException("Failed to convert to WebP: {$e->getMessage()}");
        }
    }

    public function convertToAvif(string $sourcePath, string $destinationPath, int $quality = 85): bool
    {
        try {
            // Check if AVIF is supported
            $formats = Imagick::queryFormats('AVIF');
            if (empty($formats)) {
                throw new ImageOptimizationException('AVIF format is not supported by this Imagick installation');
            }

            $image = new Imagick($sourcePath);
            
            // Set format to AVIF
            $image->setImageFormat('avif');
            $image->setImageCompressionQuality($quality);

            // Save
            $image->writeImage($destinationPath);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            throw new ImageOptimizationException("Failed to convert to AVIF: {$e->getMessage()}");
        }
    }

    public function generateBlurPlaceholder(string $sourcePath, int $width = 20, int $quality = 30): ?string
    {
        try {
            $image = new Imagick($sourcePath);
            
            // Resize to tiny dimensions
            $height = (int) ($image->getImageHeight() * ($width / $image->getImageWidth()));
            $image->resizeImage($width, $height, Imagick::FILTER_GAUSSIAN, 1);
            
            // Apply blur
            $image->blurImage(2, 1);
            
            // Convert to WebP with low quality
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);
            
            // Get as base64
            $blob = $image->getImageBlob();
            $base64 = base64_encode($blob);
            
            $image->clear();
            $image->destroy();
            
            return 'data:image/webp;base64,' . $base64;
        } catch (ImagickException $e) {
            return null;
        }
    }

    public function stripMetadata(string $path): bool
    {
        try {
            $image = new Imagick($path);
            $image->stripImage();
            $image->writeImage($path);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            return false;
        }
    }

    public function autoOrient(string $path): bool
    {
        try {
            $image = new Imagick($path);
            $this->autoOrientImage($image);
            $image->writeImage($path);
            $image->clear();
            $image->destroy();

            return true;
        } catch (ImagickException $e) {
            return false;
        }
    }

    public function getSupportedFormats(): array
    {
        $formats = ['webp'];
        
        // Check AVIF support
        if (!empty(Imagick::queryFormats('AVIF'))) {
            $formats[] = 'avif';
        }

        return $formats;
    }

    /**
     * Auto-orient image based on EXIF orientation
     */
    private function autoOrientImage(Imagick $image): void
    {
        $orientation = $image->getImageOrientation();

        switch ($orientation) {
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage('#000', 180);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage('#000', 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage('#000', -90);
                break;
        }

        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    /**
     * Optimize image for web delivery
     */
    private function optimizeForWeb(Imagick $image): void
    {
        // Set colorspace to sRGB
        $image->setColorspace(Imagick::COLORSPACE_SRGB);

        // Remove color profiles (unless needed)
        try {
            $image->profileImage('*', null);
        } catch (ImagickException $e) {
            // Ignore if no profiles
        }

        // Set sampling factors for better compression
        $image->setSamplingFactors(['2x2', '1x1', '1x1']);
    }
}
