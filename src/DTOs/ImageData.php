<?php

namespace SAHM\ImageOptimizer\DTOs;

class ImageData
{
    public function __construct(
        public readonly string $hash,
        public readonly string $src,
        public readonly string $srcset,
        public readonly string $sizes,
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $blurPlaceholder,
        public readonly bool $isLcp,
        public readonly string $alt,
        public readonly array $variants,
        public readonly array $metadata,
    ) {}

    /**
     * Create from metadata array
     */
    public static function fromMetadata(string $hash, array $metadata): self
    {
        return new self(
            hash: $hash,
            src: $metadata['optimized']['url'] ?? '',
            srcset: $metadata['srcset'] ?? '',
            sizes: $metadata['sizes'] ?? '100vw',
            width: $metadata['original']['width'] ?? 0,
            height: $metadata['original']['height'] ?? 0,
            blurPlaceholder: $metadata['blur_placeholder'] ?? null,
            isLcp: $metadata['is_lcp'] ?? false,
            alt: $metadata['alt'] ?? '',
            variants: $metadata['variants'] ?? [],
            metadata: $metadata,
        );
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            hash: $data['hash'],
            src: $data['src'],
            srcset: $data['srcset'],
            sizes: $data['sizes'],
            width: $data['width'],
            height: $data['height'],
            blurPlaceholder: $data['blur_placeholder'] ?? null,
            isLcp: $data['is_lcp'] ?? false,
            alt: $data['alt'] ?? '',
            variants: $data['variants'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'src' => $this->src,
            'srcset' => $this->srcset,
            'sizes' => $this->sizes,
            'width' => $this->width,
            'height' => $this->height,
            'blur_placeholder' => $this->blurPlaceholder,
            'is_lcp' => $this->isLcp,
            'alt' => $this->alt,
            'variants' => $this->variants,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get variant by size
     */
    public function getVariant(string $size): ?array
    {
        return $this->variants[$size] ?? null;
    }

    /**
     * Get all variant URLs
     */
    public function getVariantUrls(): array
    {
        return array_map(fn($variant) => $variant['url'], $this->variants);
    }
}
