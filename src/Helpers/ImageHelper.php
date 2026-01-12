<?php

use SAHM\ImageOptimizer\Facades\ImageOptimizer;
use SAHM\ImageOptimizer\DTOs\ImageData;

if (!function_exists('optimize_image')) {
    /**
     * Optimize an uploaded image
     */
    function optimize_image(\Illuminate\Http\UploadedFile $file, array $options = []): ImageData
    {
        return ImageOptimizer::optimize($file, $options);
    }
}

if (!function_exists('get_optimized_image')) {
    /**
     * Get optimized image by hash
     */
    function get_optimized_image(string $hash): ?ImageData
    {
        return ImageOptimizer::get($hash);
    }
}

if (!function_exists('image_srcset')) {
    /**
     * Generate srcset attribute for image
     */
    function image_srcset(ImageData $image): string
    {
        return $image->srcset;
    }
}

if (!function_exists('image_url')) {
    /**
     * Get optimized image URL
     */
    function image_url(string $hash, ?string $variant = null): ?string
    {
        $image = get_optimized_image($hash);
        
        if (!$image) {
            return null;
        }

        if ($variant && isset($image->variants[$variant])) {
            return $image->variants[$variant]['url'];
        }

        return $image->src;
    }
}

