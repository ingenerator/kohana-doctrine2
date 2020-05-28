<?php

namespace Ingenerator\KohanaDoctrine\Dependency;


use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Ingenerator\KohanaDoctrine\ExplicitClasslistAnnotationDriver;

class DoctrineFactory
{

    /**
     * Core definitions for all the services required to run with doctrine in our default configuration
     *
     * @return array
     */
    public static function definitions()
    {
        return [
            'doctrine' => [
                'cache'          => [
                    'data_cache'     => [
                        '_settings' => [
                            'class'       => DoctrineCacheFactory::class,
                            'constructor' => 'buildDataCache',
                            'shared'      => TRUE,
                        ],
                    ],
                    'compiler_cache' => [
                        '_settings' => [
                            'class'       => DoctrineCacheFactory::class,
                            'constructor' => 'buildCompilerCache',
                            'shared'      => TRUE,
                        ],
                    ],
                ],
                'config'         => [
                    'connection_config' => [
                        '_settings' => [
                            'class'     => ConnectionConfigProvider::class,
                            'arguments' => ['@database.default@'],
                        ],
                    ],
                    'metadata'          => [
                        'driver' => [
                            '_settings' => [
                                'class'     => ExplicitClasslistAnnotationDriver::class,
                                'arguments' => [
                                    '%doctrine.config.metadata.reader%',
                                    '@doctrine.entity_classes@',
                                ],
                                'shared'    => TRUE,
                            ],
                        ],
                        'reader' => [
                            '_settings' => [
                                'class'       => static::class,
                                'constructor' => 'buildMetadataReader',
                                'shared'      => TRUE,
                            ],
                        ],
                    ],
                    'orm_config'        => [
                        '_settings' => [
                            'class'       => static::class,
                            'constructor' => 'buildORMConfig',
                            'arguments'   => [
                                '%doctrine.config.metadata.driver%',
                                '%doctrine.cache.compiler_cache%',
                                '%doctrine.cache.data_cache%',
                                '@doctrine.orm@',
                            ],
                            'shared'      => TRUE,
                        ],
                    ],
                ],
                'entity_manager' => [
                    '_settings' => [
                        'class'       => static::class,
                        'constructor' => 'buildEntityManager',
                        'arguments'   => [
                            '%doctrine.config.connection_config%',
                            '%doctrine.config.orm_config%',
                            '%doctrine.event_manager%',
                        ],
                        'shared'      => TRUE,
                    ],
                ],
                'event_manager'  => [
                    '_settings' => [
                        'class'  => EventManager::class,
                        'shared' => TRUE,
                    ],
                ],
                'pdo_connection' => [
                    '_settings' => [
                        'class'       => static::class,
                        'constructor' => 'getRawPDO',
                        'arguments'   => [
                            '%doctrine.entity_manager%',
                        ],
                        'shared'      => TRUE,
                    ],
                ],
            ],
        ];
    }

    /**
     * Create a dependency config for all the doctrine event subscribers
     *
     * This will:
     *   - define each of the provided subscribers as a service in its own right
     *   - replace the default event_manager service def with one that creates it through EventDispatchFactory
     *     and attaches the subscribers as it's created.
     *
     * For simple usage:
     *
     *    DoctrineFactory::subscriberDefinitions([
     *       MySubscriber::class    => ['arguments' => ['%my.subscriber.helper%']],
     *       AuditSubscriber::class => ['arguments' => ['%warden.user_session%']],
     *    ])
     *
     * The array values are just the normal content of the service definition ['_settings' => []] array
     *
     * By default subscribers will be keyed by a sanitised version of their class name (\ characters are not
     * valid in a dependency reference). If you need to know what it is for external use, you can specify a
     * custom subscriber key like this:
     *
     *     DoctrineFactory::subscriberDefinitions([
     *       'my_subscriber'        => ['class' => MySubscriber::class, 'arguments' => ['%my.subscriber.helper%']],
     *       AuditSubscriber::class => ['arguments' => ['%warden.user_session%']],
     *    ])
     *
     * The subscriber will then be available as doctrine.subscribers.my_subscriber. Note you don't usually want to
     * do that, it's generally better if the subscriber only binds to Doctrine and if you need shared access to
     * anything it does then extract that as a shared helper/service that can be used from other places.
     *
     * Note also that subscribers CANNOT have any dependency on the doctrine.entity_manager as that will create circular
     * reference problems during construction of the entity manager.
     *
     * @param array $subscribers
     *
     * @return array
     */
    public static function subscriberDefinitions(array $subscribers)
    {
        $defs = [
            'event_manager' => [
                '_settings' => [
                    'class'       => EventDispatchFactory::class,
                    'constructor' => 'buildEventManagerWithSubscribers',
                    'arguments'   => [],
                ],
            ],
            'subscribers'   => [
            ],
        ];

        foreach ($subscribers as $class => $class_def) {
            $key = \str_replace('\\', '-', \strtolower($class));

            $defs['event_manager']['_settings']['arguments'][] = "%doctrine.subscribers.$key%";
            $defs['subscribers'][$key]['_settings']            = \array_merge(['class' => $class], $class_def);
        }

        return ['doctrine' => $defs];
    }

    /**
     * @param ConnectionConfigProvider $conn
     * @param Configuration            $config
     * @param EventManager             $event_manager
     *
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public static function buildEntityManager(
        ConnectionConfigProvider $conn,
        Configuration $config,
        EventManager $event_manager
    ) {
        return EntityManager::create(
            $conn->getConnection(),
            $config,
            $event_manager
        );
    }

    /**
     * @return CachedReader
     */
    public static function buildMetadataReader()
    {
        // Register the Doctrine annotations - this replaces the older method of loading a specific file, instead
        // just allows the autoloader to do its work. Future doctrine releases are expected to drop this outright
        // in favour of just always using autoloading
        AnnotationRegistry::registerLoader('class_exists');

        $reader = new SimpleAnnotationReader();
        $reader->addNamespace('Doctrine\ORM\Mapping');

        // The cache used here is largely irrelevant, the compiled metadata is cached by the metadata cache and if
        // so the reader is never used at all.
        return new CachedReader($reader, new ArrayCache);
    }

    /**
     * Creates and configures the ORM config
     *
     * @param MappingDriver $meta_driver
     * @param Cache         $compiler_cache
     * @param Cache         $data_cache
     * @param array|NULL    $config
     *
     * @return Configuration
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function buildORMConfig(
        MappingDriver $meta_driver,
        Cache $compiler_cache,
        Cache $data_cache,
        array $config = NULL
    ) {
        $config  = \array_merge(
            [
                'auto_gen_proxies' => \Kohana::$environment === \Kohana::DEVELOPMENT,
                'proxy_dir'        => APPPATH.'/DoctrineEntityProxy',
                'proxy_namespace'  => 'DoctrineEntityProxy',
                'custom_types'     => [],
            ],
            $config ?: []
        );
        $orm_cfg = new \Doctrine\ORM\Configuration;
        $orm_cfg->setMetadataDriverImpl($meta_driver);

        // Configure caches
        $orm_cfg->setMetadataCacheImpl($compiler_cache);
        $orm_cfg->setQueryCacheImpl($compiler_cache);
        $orm_cfg->setResultCacheImpl($data_cache);

        // Configure proxy generation
        $orm_cfg->setProxyDir($config['proxy_dir']);
        $orm_cfg->setProxyNamespace($config['proxy_namespace']);
        $orm_cfg->setAutoGenerateProxyClasses($config['auto_gen_proxies']);

        // Register any custom types
        foreach ($config['custom_types'] as $name => $class) {
            if ( ! Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        return $orm_cfg;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public static function getRawPDO(EntityManager $entityManager)
    {
        $driver = $entityManager->getConnection()->getWrappedConnection();
        if ( ! $driver instanceof \PDO) {
            throw new \InvalidArgumentException(
                'Expected Doctrine connection to be instance of '.\PDO::class.', got '.\get_class($driver)
            );
        }

        return $driver;
    }

}
