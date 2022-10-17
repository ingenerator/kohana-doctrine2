<?php


namespace test\integration\Ingenerator\KohanaDoctrine\Dependency;


use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Ingenerator\KohanaDoctrine\Dependency\ConnectionConfigProvider;
use Ingenerator\KohanaDoctrine\Dependency\DoctrineFactory;
use Ingenerator\KohanaDoctrine\ExplicitClasslistAnnotationDriver;
use PHPUnit\Framework\TestCase;

class DoctrineFactoryTest extends TestCase
{

    protected $cfg_before;

    protected $env_before;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cfg_before = \Kohana::$config;
        $this->env_before = \Kohana::$environment;
        \Kohana::$config  = new \Config;
        \Kohana::$config->attach($this->getMockBuilder(\Kohana_Config_Source::class)->getMock());
    }

    protected function tearDown(): void
    {
        \Kohana::$config      = $this->cfg_before;
        \Kohana::$environment = $this->env_before;
        parent::tearDown();
    }

    public function provider_expected_services()
    {
        return [
            ['doctrine.cache.data_cache', Cache::class, TRUE],
            ['doctrine.cache.compiler_cache', Cache::class, TRUE],
            ['doctrine.config.connection_config', ConnectionConfigProvider::class, FALSE],
            ['doctrine.config.metadata.driver', ExplicitClasslistAnnotationDriver::class, TRUE],
            ['doctrine.config.metadata.reader', CachedReader::class, TRUE],
            ['doctrine.config.orm_config', Configuration::class, TRUE],
            ['doctrine.entity_manager', EntityManager::class, TRUE],
            ['doctrine.event_manager', EventManager::class, TRUE],
            ['doctrine.pdo_connection', \PDO::class, TRUE],
        ];
    }

    /**
     * @dataProvider provider_expected_services
     */
    public function test_it_can_configure_all_published_services(
        $key,
        $expect_class,
        $expect_shared
    ) {
        $container = $this->newContainer(DoctrineFactory::definitions());
        $service   = $container->get($key);
        $this->assertInstanceOf($expect_class, $service);
        if ($expect_shared) {
            $this->assertSame(
                $service,
                $container->get($key),
                'Container should provide same service'
            );
        } else {
            $this->assertNotSame(
                $service,
                $container->get($key),
                'Container should provide different services'
            );
        }
    }

    public function provider_expected_compiler_cache()
    {
        return [
            [\Kohana::DEVELOPMENT, ArrayCache::class],
            [\Kohana::STAGING, ApcuCache::class],
            [\Kohana::PRODUCTION, ApcuCache::class],
        ];
    }

    /**
     * @dataProvider provider_expected_compiler_cache
     */
    public function test_it_uses_apcu_compiler_cache_by_default_or_array_cache_in_dev($env, $expect)
    {
        \Kohana::$environment = $env;
        $container            = $this->newContainer(DoctrineFactory::definitions());
        $cache                = $container->get('doctrine.cache.compiler_cache');
        $this->assertInstanceOf($expect, $cache);
        $config = $container->get('doctrine.config.orm_config');
        /** @var Configuration $config */
        $this->assertSame(
            $cache,
            $config->getMetadataCacheImpl(),
            'Should use compiler cache for metadata'
        );
        $this->assertSame(
            $cache,
            $config->getQueryCacheImpl(),
            'Should use compiler cache for parsed queries'
        );
    }

    public function provider_expected_data_cache()
    {
        return [
            [\Kohana::DEVELOPMENT],
            [\Kohana::PRODUCTION],
        ];
    }

    /**
     * @dataProvider provider_expected_data_cache
     */
    public function test_it_uses_array_cache_for_data_cache_by_default_in_all_environments($env)
    {
        \Kohana::$environment = $env;
        $container            = $this->newContainer(DoctrineFactory::definitions());
        $cache                = $container->get('doctrine.cache.data_cache');
        $this->assertInstanceOf(ArrayCache::class, $cache);
        $config = $container->get('doctrine.config.orm_config');
        /** @var Configuration $config */
        $this->assertSame(
            $cache,
            $config->getResultCacheImpl(),
            'Should use data cache for result caching'
        );
        $this->assertNull(
            $config->getHydrationCacheImpl(),
            'Should not assign hydration cache by default'
        );
    }

    public function test_it_attaches_database_config_from_kohana_config()
    {
        $db_cfg = \Kohana::$config->load('database');
        $db_cfg->exchangeArray(
            [
                'default' => [
                    'connection' => [
                        'hostname' => 'localhost',
                        'database' => 'mydb',
                        'username' => 'me',
                        'password' => 'secret',
                    ],
                ],
            ]
        );
        $container = $this->newContainer(DoctrineFactory::definitions());
        $cfg       = $container->get('doctrine.config.connection_config');
        /** @var ConnectionConfigProvider $cfg */
        $this->assertSame(
            [
                'driver'        => 'pdo_mysql',
                'host'          => 'localhost',
                'user'          => 'me',
                'password'      => 'secret',
                'dbname'        => 'mydb',
                'charset'       => 'utf8',
                'driverOptions' => [
                    \PDO::ATTR_TIMEOUT => 5,
                ],
            ],
            $cfg->getConnection()
        );
    }

    public function test_it_loads_entity_classes_from_kohana_config_and_can_read_metadata_from_annotations(
    )
    {
        $this->givenDoctrineConfig(['orm' => ['entity_classes' => [SomeEntity::class]]]);
        $container = $this->newContainer(DoctrineFactory::definitions());
        $em        = $container->get('doctrine.entity_manager');
        /** @var EntityManager $em */
        $meta = $em->getClassMetadata(SomeEntity::class);
        $this->assertEquals(['whatever'], $meta->getColumnNames());
    }

    public function provider_proxy_generation()
    {
        return [
            [
                \Kohana::DEVELOPMENT,
                [],
                [
                    'autogenerate'    => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
                    'proxy_dir'       => APPPATH.'/DoctrineEntityProxy',
                    'proxy_namespace' => 'DoctrineEntityProxy',
                ],
            ],
            [
                \Kohana::PRODUCTION,
                [],
                [
                    'autogenerate'    => AbstractProxyFactory::AUTOGENERATE_NEVER,
                    'proxy_dir'       => APPPATH.'/DoctrineEntityProxy',
                    'proxy_namespace' => 'DoctrineEntityProxy',
                ],
            ],
            [
                \Kohana::PRODUCTION,
                [
                    'orm' => [
                        'auto_gen_proxies' => TRUE,
                        'proxy_dir'        => '/foo/bar/proxy',
                        'proxy_namespace'  => 'Some\Namespace',
                    ],
                ],
                [
                    'autogenerate'    => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
                    'proxy_dir'       => '/foo/bar/proxy',
                    'proxy_namespace' => 'Some\Namespace',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider_proxy_generation
     */
    public function test_it_configures_proxy_generation_from_defaults_or_from_config_overrides(
        $env,
        $cfg,
        $expect
    ) {
        \Kohana::$environment = $env;
        $this->givenDoctrineConfig($cfg);
        $container = $this->newContainer(DoctrineFactory::definitions());
        $config    = $container->get('doctrine.config.orm_config');
        /** @var Configuration $config */
        $this->assertSame(
            $expect,
            [
                'autogenerate'    => $config->getAutoGenerateProxyClasses(),
                'proxy_dir'       => $config->getProxyDir(),
                'proxy_namespace' => $config->getProxyNamespace(),
            ]
        );
    }

    public function test_it_registers_any_configured_custom_types_during_orm_configuration()
    {
        $this->givenDoctrineConfig(
            [
                'orm' => [
                    'custom_types' => [
                        'giant_array' => GiantArrayType::class,
                    ],
                ],
            ]
        );
        $container = $this->newContainer(DoctrineFactory::definitions());
        $this->assertFalse(
            Type::hasType('giant_array'),
            'Type should not be registered before config'
        );
        $container->get('doctrine.config.orm_config');
        $this->assertTrue(Type::hasType('giant_array'), 'Type should be registered after config');
        $this->assertInstanceOf(GiantArrayType::class, Type::getType('giant_array'));
    }

    public function test_it_provides_the_raw_pdo_connection_from_doctrine()
    {
        $container = $this->newContainer(DoctrineFactory::definitions());
        $pdo       = $container->get('doctrine.pdo_connection');
        $this->assertInstanceOf(\PDO::class, $pdo);
        $em = $container->get('doctrine.entity_manager');
        /** @var EntityManager $em */
        $this->assertSame($pdo, $em->getConnection()->getNativeConnection());
    }

    public function test_it_attaches_event_manager_to_doctrine()
    {
        $container = $this->newContainer(DoctrineFactory::definitions());
        $em        = $container->get('doctrine.entity_manager');
        $evt       = $container->get('doctrine.event_manager');
        /** @var EntityManager $em */
        $this->assertSame($evt, $em->getEventManager());
    }

    public function test_it_attaches_event_subscribers_if_defined()
    {
        $defs      = \Arr::merge(
            DoctrineFactory::definitions(),
            DoctrineFactory::subscriberDefinitions(
                [
                    MySubscriber::class      => ['arguments' => [Events::prePersist]],
                    MyOtherSubscriber::class => ['arguments' => [Events::prePersist]],
                ]
            )
        );
        $container = $this->newContainer($defs);
        $em        = $container->get('doctrine.entity_manager');
        /** @var EntityManager $em */
        $listeners = $em->getEventManager()->getListeners(Events::prePersist);
        $this->assertCount(2, $listeners, 'Should have 2 listeners');
        $this->assertInstanceOf(MySubscriber::class, \array_shift($listeners));
        $this->assertInstanceOf(MyOtherSubscriber::class, \array_shift($listeners));
    }

    /**
     * @param array $cfg
     */
    protected function givenDoctrineConfig(array $cfg)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $group = \Kohana::$config->load('doctrine');
        $group->exchangeArray($cfg);
    }

    /**
     * @param array $definitions
     *
     * @return \Dependency_Container
     */
    protected function newContainer(array $definitions)
    {
        $list      = \Dependency_Definition_List::factory()->from_array($definitions);
        $container = new \Dependency_Container($list);

        return $container;
    }

}

/**
 * @Entity
 */
class SomeEntity
{

    /**
     * @Id
     * @Column(nullable=true)
     */
    protected $whatever;
}

class GiantArrayType extends ArrayType
{
}

class MySubscriber implements EventSubscriber
{
    private $event;

    public function __construct($event) { $this->event = $event; }

    public function getSubscribedEvents()
    {
        return [$this->event];
    }
}

class MyOtherSubscriber extends MySubscriber
{
}
