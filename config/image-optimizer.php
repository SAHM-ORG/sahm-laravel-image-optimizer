<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('IMAGE_OPTIMIZER_DISK', 'public'),
        'base_path' => env('IMAGE_OPTIMIZER_BASE_PATH', 'images'),
        'paths' => [
            'originals' => 'originals',
            'optimized' => 'optimized',
            'cache' => 'cache',
        ],
        'hash_distribution' => true,
        'hash_depth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Configuration
    |--------------------------------------------------------------------------
    */
    'processing' => [
        'processor' => env('IMAGE_OPTIMIZER_PROCESSOR', 'imagick'),
        'fallback' => true,
        'default_quality' => 85,
        'qualities' => [
            320 => 80,
            640 => 85,
            1024 => 85,
            1920 => 88,
            2560 => 90,
        ],
        'sizes' => [320, 640, 1024, 1920],
        'max_width' => 2560,
        'max_height' => 2560,
        'strip_metadata' => true,
        'auto_orient' => true,
        'blur_placeholder' => [
            'enabled' => true,
            'width' => 20,
            'quality' => 30,
        ],
        'progressive' => true,
        'memory_limit' => 256,
    ],

    /*
    |--------------------------------------------------------------------------
    | Format Configuration
    |--------------------------------------------------------------------------
    */
    'formats' => [
        'output' => env('IMAGE_OPTIMIZER_FORMAT', 'webp'),
        'keep_original_format' => false,
        'webp' => [
            'method' => 6,
            'lossless' => false,
            'alpha_quality' => 100,
        ],
        'avif' => [
            'quality' => 85,
            'speed' => 6,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'sahm_img_',
        'store' => env('IMAGE_OPTIMIZER_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('IMAGE_OPTIMIZER_QUEUE', false),
        'connection' => env('IMAGE_OPTIMIZER_QUEUE_CONNECTION', null),
        'queue' => env('IMAGE_OPTIMIZER_QUEUE_NAME', 'default'),
        'threshold' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'max_file_size' => 10240,
        'min_width' => 10,
        'min_height' => 10,
        'allowed_mimes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'verify_image_type' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimization Presets
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'avatar' => [
            'sizes' => [64, 128, 256],
            'quality' => 85,
            'max_width' => 512,
            'max_height' => 512,
        ],
        'thumbnail' => [
            'sizes' => [150, 300],
            'quality' => 80,
            'max_width' => 600,
            'max_height' => 600,
        ],
        'gallery' => [
            'sizes' => [640, 1024, 1920],
            'quality' => 85,
        ],
        'hero' => [
            'sizes' => [768, 1024, 1920, 2560],
            'quality' => 88,
            'is_lcp' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => false,
        'prefix' => 'api/images',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lighthouse Optimization
    |--------------------------------------------------------------------------
    */
    'lighthouse' => [
        'auto_srcset' => true,
        'default_sizes' => '100vw',
        'sizes_presets' => [
            'full' => '100vw',
            'half' => '(min-width: 1024px) 50vw, 100vw',
            'third' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
            'content' => '(min-width: 1024px) 800px, 100vw',
        ],
    ],

];
