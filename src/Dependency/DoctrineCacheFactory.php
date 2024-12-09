<?php


namespace Ingenerator\KohanaDoctrine\Dependency;


use Kohana;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DoctrineCacheFactory
{

    /**
     * The data cache is used for query result etc caching.
     *
     * It is always present, but only used if specific queries / operations indicate that they're cacheable. Also by
     * default it uses an ArrayAdapter in all environments. This ensures code paths don't need to vary to cope with the
     * presence / absence of cache. You'll of course want to switch to a suitable persistent cache in projects where
     * you actually want to cache in production.
     */
    public static function buildDataCache(): CacheItemPoolInterface
    {
        return new ArrayAdapter();
    }

    /**
     * The compiler cache is used for metadata and query compilation caching
     *
     * By default it uses ArrayAdapter in local development (to ensure changes are picked up live) and
     * Apcu in all other environments. Note that this cache is tied to the codebase deployed, so can
     * and should be separate on all instances in a cluster, there's no need to swap out for a shared
     * (e.g. memcached) cache when scaling.
     */
    public static function buildCompilerCache(): CacheItemPoolInterface
    {
        return match (\Kohana::$environment) {
            Kohana::DEVELOPMENT => new ArrayAdapter(),
            default => new ApcuAdapter('doctrine-meta')
        };
    }

}
