<?php

namespace SAHM\ImageOptimizer\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ImageCacheService
{
    private CacheRepository $cache;
    private bool $enabled;
    private int $ttl;
    private string $prefix;

    public function __construct(CacheRepository $cache, array $config)
    {
        $this->cache = $cache;
        $this->enabled = $config['enabled'] ?? true;
        $this->ttl = $config['ttl'] ?? 3600;
        $this->prefix = $config['prefix'] ?? 'sahm_img_';
    }

    /**
     * Get from cache
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        return $this->cache->get($this->prefix . $key);
    }

    /**
     * Put in cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->put($this->prefix . $key, $value, $ttl ?? $this->ttl);
    }

    /**
     * Forget cache key
     */
    public function forget(string $key): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->forget($this->prefix . $key);
    }

    /**
     * Remember value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        return $this->cache->remember($this->prefix . $key, $ttl ?? $this->ttl, $callback);
    }

    /**
     * Clear all image cache
     */
    public function flush(): void
    {
        if (!$this->enabled) {
            return;
        }

        // This is simplified - in production you might want to track all keys
        $this->cache->flush();
    }
}
