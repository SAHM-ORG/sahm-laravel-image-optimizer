<?php

namespace SAHM\ImageOptimizer\Tests\Feature;

use SAHM\ImageOptimizer\Tests\TestCase;
use SAHM\ImageOptimizer\Facades\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageOptimizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** @test */
    public function it_can_optimize_an_image(): void
    {
        $imagePath = $this->createTestImage(800, 600);
        
        $file = new UploadedFile(
            $imagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageData = ImageOptimizer::optimize($file);

        $this->assertNotNull($imageData);
        $this->assertNotEmpty($imageData->src);
        $this->assertNotEmpty($imageData->srcset);
        $this->assertEquals(800, $imageData->width);
        $this->assertEquals(600, $imageData->height);

        unlink($imagePath);
    }

    /** @test */
    public function it_generates_responsive_variants(): void
    {
        $imagePath = $this->createTestImage(2000, 1500);
        
        $file = new UploadedFile(
            $imagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageData = ImageOptimizer::optimize($file, [
            'sizes' => [320, 640, 1024],
        ]);

        $this->assertNotEmpty($imageData->variants);
        $this->assertArrayHasKey('320w', $imageData->variants);
        $this->assertArrayHasKey('640w', $imageData->variants);
        $this->assertArrayHasKey('1024w', $imageData->variants);

        unlink($imagePath);
    }

    /** @test */
    public function it_can_retrieve_optimized_image_by_hash(): void
    {
        $imagePath = $this->createTestImage(800, 600);
        
        $file = new UploadedFile(
            $imagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $optimized = ImageOptimizer::optimize($file);
        $hash = $optimized->hash;

        $retrieved = ImageOptimizer::get($hash);

        $this->assertNotNull($retrieved);
        $this->assertEquals($hash, $retrieved->hash);
        $this->assertEquals($optimized->src, $retrieved->src);

        unlink($imagePath);
    }

    /** @test */
    public function it_can_delete_optimized_image(): void
    {
        $imagePath = $this->createTestImage(800, 600);
        
        $file = new UploadedFile(
            $imagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageData = ImageOptimizer::optimize($file);
        $hash = $imageData->hash;

        $deleted = ImageOptimizer::delete($hash);

        $this->assertTrue($deleted);
        $this->assertNull(ImageOptimizer::get($hash));

        unlink($imagePath);
    }

    /** @test */
    public function it_uses_preset_configuration(): void
    {
        $imagePath = $this->createTestImage(800, 600);
        
        $file = new UploadedFile(
            $imagePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageData = ImageOptimizer::optimize($file, [
            'preset' => 'thumbnail',
        ]);

        $this->assertNotNull($imageData);

        unlink($imagePath);
    }
}
