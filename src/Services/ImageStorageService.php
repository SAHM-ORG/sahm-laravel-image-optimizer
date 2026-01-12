<?php

namespace SAHM\ImageOptimizer\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use SAHM\ImageOptimizer\Exceptions\ImageOptimizationException;

class ImageStorageService
{
    private string $disk;
    private string $basePath;
    private array $paths;
    private bool $hashDistribution;
    private int $hashDepth;

    public function __construct(array $config)
    {
        $this->disk = $config['disk'] ?? 'public';
        $this->basePath = $config['base_path'] ?? 'images';
        $this->paths = $config['paths'] ?? [];
        $this->hashDistribution = $config['hash_distribution'] ?? true;
        $this->hashDepth = $config['hash_depth'] ?? 2;
    }

    /**
     * Generate hash for file
     */
    public function generateHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Generate hash from path
     */
    public function generateHashFromPath(string $path): string
    {
        return hash_file('sha256', $path);
    }

    /**
     * Get hash-based directory path
     */
    public function getHashPath(string $hash): string
    {
        if (!$this->hashDistribution) {
            return $hash;
        }

        // Create distributed path: ab/cdef.../
        $parts = [];
        $offset = 0;

        for ($i = 0; $i < $this->hashDepth; $i++) {
            $parts[] = substr($hash, $offset, 2);
            $offset += 2;
        }

        $parts[] = substr($hash, $offset);

        return implode('/', $parts);
    }

    /**
     * Store original file
     */
    public function storeOriginal(UploadedFile $file, string $hash): string
    {
        $hashPath = $this->getHashPath($hash);
        $filename = $this->sanitizeFilename($file->getClientOriginalName());
        $path = $this->buildPath('originals', $hashPath, $filename);

        // Store file
        Storage::disk($this->disk)->put(
            $path,
            file_get_contents($file->getRealPath())
        );

        return $path;
    }

    /**
     * Get optimized path
     */
    public function getOptimizedPath(string $hash, string $filename, ?string $variant = null, string $format = 'webp'): string
    {
        $hashPath = $this->getHashPath($hash);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $suffix = $variant ? "-{$variant}" : '';
        $newFilename = "{$basename}{$suffix}.{$format}";

        return $this->buildPath('optimized', $hashPath, $newFilename);
    }

    /**
     * Get metadata path
     */
    public function getMetadataPath(string $hash): string
    {
        $hashPath = $this->getHashPath($hash);
        return $this->buildPath('optimized', $hashPath, 'meta.json');
    }

    /**
     * Save metadata
     */
    public function saveMetadata(string $hash, array $metadata): void
    {
        $path = $this->getMetadataPath($hash);
        
        Storage::disk($this->disk)->put(
            $path,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Get metadata
     */
    public function getMetadata(string $hash): ?array
    {
        $path = $this->getMetadataPath($hash);

        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $json = Storage::disk($this->disk)->get($path);
        return json_decode($json, true);
    }

    /**
     * Delete all files for hash
     */
    public function delete(string $hash): bool
    {
        $hashPath = $this->getHashPath($hash);
        
        // Delete original directory
        $originalDir = $this->buildPath('originals', $hashPath);
        if (Storage::disk($this->disk)->exists($originalDir)) {
            Storage::disk($this->disk)->deleteDirectory($originalDir);
        }

        // Delete optimized directory
        $optimizedDir = $this->buildPath('optimized', $hashPath);
        if (Storage::disk($this->disk)->exists($optimizedDir)) {
            Storage::disk($this->disk)->deleteDirectory($optimizedDir);
        }

        return true;
    }

    /**
     * Check if hash exists
     */
    public function exists(string $hash): bool
    {
        return Storage::disk($this->disk)->exists($this->getMetadataPath($hash));
    }

    /**
     * Get public URL
     */
    public function getUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get full filesystem path
     */
    public function getFullPath(string $path): string
    {
        return Storage::disk($this->disk)->path($path);
    }

    /**
     * Get file size
     */
    public function getSize(string $path): int
    {
        return Storage::disk($this->disk)->size($path);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Build full path
     */
    private function buildPath(string $type, string $hashPath, ?string $filename = null): string
    {
        $parts = [
            $this->basePath,
            $this->paths[$type] ?? $type,
            $hashPath,
        ];

        if ($filename) {
            $parts[] = $filename;
        }

        return implode('/', array_filter($parts));
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove special characters, keep only alphanumeric, dots, dashes, underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = preg_replace('/\.+/', '.', $filename); // Remove multiple dots
        $filename = trim($filename, '.-_');
        
        return $filename ?: 'image.jpg';
    }
}

