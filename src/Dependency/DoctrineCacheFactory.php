<?php


namespace Ingenerator\KohanaDoctrine\Dependency;


use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;

class DoctrineCacheFactory
{

    /**
     * The data cache is used for query result etc caching.
     *
     * It is always present, but only used if specific queries / operations indicate that they're cacheable. Also by
     * default it uses an ArrayCache in all environments. This ensures code paths don't need to vary to cope with the
     * presence / absence of cache. You'll of course want to switch to a suitable persistent cache in projects where
     * you actually want to cache in production.
     *
     * @return ArrayCache
     */
    public static function buildDataCache()
    {
        return new ArrayCache;
    }

    /**
     * The compiler cache is used for metadata and query compilation caching
     *
     * By default it uses ArrayCache in local development (to ensure changes are picked up live) and
     * Apcu in all other environments. Note that this cache is tied to the codebase deployed, so can
     * and should be separate on all instances in a cluster, there's no need to swap out for a shared
     * (e.g. memcached) cache when scaling.
     *
     * @return ApcuCache|ArrayCache
     */
    public static function buildCompilerCache()
    {
        return \Kohana::$environment === \Kohana::DEVELOPMENT ? new ArrayCache : new ApcuCache;
    }

}