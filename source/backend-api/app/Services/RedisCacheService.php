<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisCacheService
{
    /**
     * Cache prefix to avoid key collisions
     */
    protected string $prefix = 'employee_api:';

    /**
     * Default cache TTL in seconds (5 minutes)
     */
    protected int $defaultTtl = 300;

    /**
     * Whether to use Redis cache
     */
    protected bool $enabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Only enable Redis caching in production and when Redis is available
        $this->enabled = app()->environment('production') &&
            config('database.redis.client') !== 'predis';
    }

    /**
     * Get an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->enabled) {
            return $default;
        }

        try {
            $cachedValue = Redis::get($this->prefix . $key);

            if ($cachedValue === null) {
                return $default;
            }

            return unserialize($cachedValue);
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl TTL in seconds
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $ttl = $ttl ?? $this->defaultTtl;
            $serialized = serialize($value);

            Redis::setex($this->prefix . $key, $ttl, $serialized);

            // Track the key
            $this->trackKey($key);

            return true;
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            Redis::del($this->prefix . $key);
            return true;
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Flush all cached items with this prefix
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Get all keys with our prefix
            $keys = Redis::keys($this->prefix . '*');

            if (count($keys) > 0) {
                Redis::del(...$keys);
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return (bool) Redis::exists($this->prefix . $key);
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get or retrieve an item from cache
     *
     * @param string $key
     * @param \Closure $callback Function to generate value if not in cache
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null)
    {
        // If Redis is not enabled, just call the callback
        if (!$this->enabled) {
            return $callback();
        }

        try {
            // Check if we have a value in cache
            $value = $this->get($key);

            // If we don't have a value, generate one
            if ($value === null) {
                $value = $callback();

                // Only cache if the value is not null
                if ($value !== null) {
                    $this->put($key, $value, $ttl);
                }
            }

            return $value;
        } catch (\Exception $e) {
            Log::warning("Redis cache error: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Track a cache key for management
     *
     * @param string $key
     * @return void
     */
    protected function trackKey(string $key): void
    {
        try {
            // Store in a Redis set for tracking
            Redis::sadd($this->prefix . 'tracked_keys', $key);
        } catch (\Exception $e) {
            Log::warning("Redis tracking error: " . $e->getMessage());
        }
    }

    /**
     * Get all tracked keys
     *
     * @return array
     */
    public function getTrackedKeys(): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            return Redis::smembers($this->prefix . 'tracked_keys') ?: [];
        } catch (\Exception $e) {
            Log::warning("Redis tracking error: " . $e->getMessage());
            return [];
        }
    }
}
