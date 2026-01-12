<?php

namespace SAHM\ImageOptimizer\Tests\Unit;

use SAHM\ImageOptimizer\Tests\TestCase;
use SAHM\ImageOptimizer\Processors\GdProcessor;
use SAHM\ImageOptimizer\Processors\ImagickProcessor;

class ProcessorTest extends TestCase
{
    /** @test */
    public function gd_processor_is_available(): void
    {
        $processor = new GdProcessor();
        
        $this->assertTrue($processor->isAvailable());
        $this->assertEquals('gd', $processor->getName());
    }

    /** @test */
    public function gd_processor_can_get_image_info(): void
    {
        $processor = new GdProcessor();
        $imagePath = $this->createTestImage(800, 600);

        $info = $processor->getImageInfo($imagePath);

        $this->assertEquals(800, $info['width']);
        $this->assertEquals(600, $info['height']);
        $this->assertEquals('jpg', $info['format']);

        unlink($imagePath);
    }

    /** @test */
    public function gd_processor_can_resize_image(): void
    {
        $processor = new GdProcessor();
        $sourcePath = $this->createTestImage(800, 600);
        $destPath = sys_get_temp_dir() . '/resized-' . uniqid() . '.jpg';

        $result = $processor->resize($sourcePath, $destPath, 400);

        $this->assertTrue($result);
        $this->assertFileExists($destPath);

        $info = getimagesize($destPath);
        $this->assertEquals(400, $info[0]);
        $this->assertEquals(300, $info[1]); // Maintains aspect ratio

        unlink($sourcePath);
        unlink($destPath);
    }

    /** @test */
    public function gd_processor_can_convert_to_webp(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('WebP not supported');
        }

        $processor = new GdProcessor();
        $sourcePath = $this->createTestImage(800, 600);
        $destPath = sys_get_temp_dir() . '/converted-' . uniqid() . '.webp';

        $result = $processor->convertToWebP($sourcePath, $destPath, 85);

        $this->assertTrue($result);
        $this->assertFileExists($destPath);

        unlink($sourcePath);
        unlink($destPath);
    }
}
