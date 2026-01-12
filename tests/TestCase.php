<?php

namespace SAHM\ImageOptimizer\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SAHM\ImageOptimizer\ImageOptimizerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup
    }

    protected function getPackageProviders($app): array
    {
        return [
            ImageOptimizerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ImageOptimizer' => \SAHM\ImageOptimizer\Facades\ImageOptimizer::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default config
        $app['config']->set('image-optimizer.storage.disk', 'local');
        $app['config']->set('image-optimizer.processing.processor', 'gd');
    }

    /**
     * Create a test image file
     */
    protected function createTestImage(int $width = 800, int $height = 600, string $format = 'jpg'): string
    {
        $path = sys_get_temp_dir() . '/test-image-' . uniqid() . '.' . $format;
        
        $image = imagecreatetruecolor($width, $height);
        
        // Fill with random color
        $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($image, 0, 0, $color);

        // Save based on format
        match ($format) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 90),
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, 90),
            default => imagejpeg($image, $path, 90),
        };

        imagedestroy($image);

        return $path;
    }
}
