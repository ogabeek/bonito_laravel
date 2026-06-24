<?php

namespace App\Concerns;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache a value only when it is non-empty.
 *
 * A transient upstream failure (e.g. a Google Sheets timeout returning []) must
 * never be cached, or it would hide real data for the whole TTL. Shared by the
 * Sheets-backed services so the "don't cache empty" rule lives in one place.
 */
trait CachesNonEmpty
{
    /**
     * Return the cached value, or compute it and cache it only when non-empty.
     */
    protected function rememberNonEmpty(string $key, int $ttl, Closure $callback): mixed
    {
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $fresh = $callback();

        if (! empty($fresh)) {
            Cache::put($key, $fresh, $ttl);
        }

        return $fresh;
    }
}
