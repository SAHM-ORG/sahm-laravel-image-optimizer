# SAHM Laravel Image Optimizer

Professional backend image optimization for Laravel 11 & 12. Convert images to WebP/AVIF, generate responsive variants, optimize for Lighthouse and Core Web Vitals. **No database required.**

[![Latest Version](https://img.shields.io/packagist/v/sahm/laravel-image-optimizer.svg?style=flat-square)](https://packagist.org/packages/sahm/laravel-image-optimizer)
[![Total Downloads](https://img.shields.io/packagist/dt/sahm/laravel-image-optimizer.svg?style=flat-square)](https://packagist.org/packages/sahm/laravel-image-optimizer)
[![License](https://img.shields.io/packagist/l/sahm/laravel-image-optimizer.svg?style=flat-square)](https://packagist.org/packages/sahm/laravel-image-optimizer)

---

## ✨ Features

- ✅ **No Database Required** - File-based storage with JSON metadata
- ✅ **WebP & AVIF Support** - Modern image formats
- ✅ **Responsive Variants** - Automatic generation of multiple sizes
- ✅ **Lighthouse Optimized** - Pass Core Web Vitals (LCP, FCP)
- ✅ **Native PHP** - Uses Imagick or GD (no external dependencies)
- ✅ **Smart Processing** - Automatic quality detection
- ✅ **Queue Support** - Background processing for large images
- ✅ **Blur Placeholders** - LQIP (Low Quality Image Placeholders)
- ✅ **CDN Ready** - Works with any CDN
- ✅ **Artisan Commands** - Bulk optimization & cleanup
- ✅ **Presets** - Predefined configs (avatar, thumbnail, hero, etc.)
- ✅ **70-80% Size Reduction** - Without visible quality loss

---

## 📋 Requirements

- **PHP:** 8.2 or higher
- **Laravel:** 11.x or 12.x
- **Extensions:** Imagick (preferred) or GD

---

## 🚀 Installation

Install via Composer:

```bash
composer require sahm-org/sahm-laravel-image-optimizer
```

Publish configuration:

```bash
php artisan vendor:publish --tag=image-optimizer-config
```

## 🎯 Quick Start

### Basic Usage
```php
use SAHM\ImageOptimizer\Facades\ImageOptimizer;

// Optimize uploaded image
$imageData = ImageOptimizer::optimize($request->file('photo'));

// Get image data
$data = $imageData->toArray();
/*
[
    'hash' => 'a3f7b2c1...',
    'src' => '/storage/images/optimized/.../photo.webp',
    'srcset' => '...photo-320w.webp 320w, ...photo-640w.webp 640w, ...',
    'sizes' => '100vw',
    'width' => 1920,
    'height' => 1080,
    'blur_placeholder' => 'data:image/webp;base64,...',
    'is_lcp' => false,
    'alt' => '',
    'variants' => [...],
]
*/
```

### With Options
```php
$imageData = ImageOptimizer::optimize($request->file('photo'), [
    'quality' => 85,
    'sizes' => [320, 640, 1024, 1920],
    'is_lcp' => true,
    'alt' => 'Hero image',
]);
```

### Using Presets
```php
// Avatar preset
$avatar = ImageOptimizer::optimize($request->file('avatar'), [
    'preset' => 'avatar',
]);

// Hero preset (optimized for LCP)
$hero = ImageOptimizer::optimize($request->file('hero'), [
    'preset' => 'hero',
]);
```

### Retrieve Optimized Image
```php
// By hash (store this in your database)
$hash = 'a3f7b2c1...';
$imageData = ImageOptimizer::get($hash);

if ($imageData) {
    echo $imageData->src;
    echo $imageData->srcset;
}
```

## 🎨 Frontend Integration

### Laravel Blade
```blade
@if($imageData)
<img 
    src="{{ $imageData->src }}"
    srcset="{{ $imageData->srcset }}"
    sizes="{{ $imageData->sizes }}"
    width="{{ $imageData->width }}"
    height="{{ $imageData->height }}"
    loading="{{ $imageData->isLcp ? 'eager' : 'lazy' }}"
    fetchpriority="{{ $imageData->isLcp ? 'high' : 'auto' }}"
    alt="{{ $imageData->alt }}"
/>
@endif
```

### Inertia/Vue
```php
// Controller
return Inertia::render('Page', [
    'hero' => ImageOptimizer::optimize($file, ['preset' => 'hero'])->toArray(),
]);
```

```vue
<!-- Component -->
<template>
  <img
    :src="hero.src"
    :srcset="hero.srcset"
    :sizes="hero.sizes"
    :width="hero.width"
    :height="hero.height"
    :loading="hero.is_lcp ? 'eager' : 'lazy'"
    :fetchpriority="hero.is_lcp ? 'high' : 'auto'"
    :alt="hero.alt"
  />
</template>
```

## 🛠 Artisan Commands
```bash
# Check system info
php artisan image-optimizer:info

# Optimize directory
php artisan image-optimizer:optimize storage/app/public/uploads --quality=85

# Cleanup old images
php artisan image-optimizer:cleanup --days=30
```

## 📊 Performance

### Typical Results:
| Metric | Before | AfterDownload table as XLSX fileDownload table as XLSX file |
| --- | --- | --- |
| File Size | 2.5 MB | 650 KB (74% reduction) |
| Lighthouse Performance | 65 | 94 |
| LCP | 4.2s | 1.8s |
| FCP | 3.1s | 1.2s |

## 🏢 About SAHM
Created and maintained by SAHM.