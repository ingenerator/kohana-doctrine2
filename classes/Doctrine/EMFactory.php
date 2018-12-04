<?php
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

/**
 *  Creates an instance of the Doctrine Entity Manager with the appropriate configuration
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2013 inGenerator Ltd
 * @licence    BSD
 * @package    kohana-doctrine2
 * @subpackage factories
 */
class Doctrine_EMFactory {

	/**
	 * @var Config the Kohana config loader
	 */
	protected $config = NULL;

	/**
	 * @var int the current Kohana environment
	 */
	protected $environment = NULL;

	/**
	 * Create an instance, optionally injecting any external dependencies which are otherwise loaded from the Kohana
	 * static methods and classes.
	 *
	 * @param Config $config      the Kohana config loader - loaded from Kohana::$config if required
	 * @param int    $environment a Kohana environment configuration - loaded from Kohana::$environment if required
	 *
	 * @return Doctrine_EMFactory
	 */
	public function __construct(Config $config = NULL, $environment = NULL)
	{
		$this->config = ($config !== NULL) ? $config : Kohana::$config;
		$this->environment = ($environment !== NULL) ? $environment : Kohana::$environment;
	}

	/**
	 * Creates a new EntityManager instance based on the provided configuration.
	 *
	 *     $factory = new Doctrine_EMFactory;
	 *     $em = $factory->entity_manager();
	 *
	 * @param string $db_group the name of the Kohana database config group to get connection information from
	 *
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function entity_manager($db_group = 'default')
	{
		$config = $this->config->load('doctrine');

		// Ensure the composer autoloader is registered
		require_once $config['composer_vendor_path'].'autoload.php';

		// Create the Configuration class
		$orm_config = new Configuration;

		// Create the metadata driver
		$driver = $this->create_annotation_driver();
		$orm_config->setMetadataDriverImpl($driver);

		// Configure the proxy directory and namespace
		$orm_config->setProxyDir($config['proxy_dir']);
		$orm_config->setProxyNamespace($config['proxy_namespace']);

		if ($config->get('use_underscore_naming_strategy'))
		{
			$naming_strategy = new UnderscoreNamingStrategy($config->get('case_underscore_naming_strategy'));
			$orm_config->setNamingStrategy($naming_strategy);
		}

		// Configure environment-specific options
		if ($this->environment === Kohana::DEVELOPMENT)
		{
			$orm_config->setAutoGenerateProxyClasses(TRUE);
			$cache = new ArrayCache;
		}
		else
		{
			$orm_config->setAutoGenerateProxyClasses(FALSE);
			$cache = new ApcCache;
		}

		// Set the cache drivers
		$orm_config->setMetadataCacheImpl($cache);
		$orm_config->setQueryCacheImpl($cache);

		// Create the Entity Manager with the database connection information
		$em = EntityManager::create(
			$this->get_connection_config($db_group),
			$orm_config
		);

		$this->register_custom_types($config);

		return $em;
	}

	/**
	 * Create the Doctrine DBAL connection options from the Kohana database configuration
	 *
	 * @param string $db_group Name of the database group to load connection information from
	 *
	 * @return array configuration data for Doctrine DBAL
	 * @throws InvalidArgumentException if the Kohana database type has not been mapped to a Doctrine driver
	 */
	protected function get_connection_config($db_group)
	{
		// Load the database configuration for this group
		$config = $this->config->load('database');
		if ( ! isset($config[$db_group]))
		{
			throw new \InvalidArgumentException("Could not find a database config for the '$db_group' group");
		}
		$config = $config[$db_group];

		// Map the Kohana db configuration to Doctrine driver and options
		switch($config['type'])
		{
			case 'MySQL':
				return array(
					'driver'   => 'pdo_mysql',
					'host'     => $config['connection']['hostname'],
					'user'     => $config['connection']['username'],
				    'password' => $config['connection']['password'],
				    'dbname'   => $config['connection']['database'],
				    'charset'  => $config['charset']
				);

			default:
				throw new \InvalidArgumentException("Could not map database type '".$config['type']."' to a Doctrine driver");
		}
	}

	/**
	 * Register any custom doctrine type handlers. Configure in the doctrine config like:
	 *
	 *     return array(
	 *         'custom_types' => array(
	 *             'money' => 'My\Money\TypeClass'
	 *         )
	 *     );
	 *
	 * @link  http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
	 * @param array $config
	 */
	protected function register_custom_types($config)
	{
		foreach (Arr::get($config, 'custom_types', array()) as $name => $className)
		{
			if ( ! Type::hasType($name))
			{
				Type::addType($name, $className);
			}
		}
	}

	/**
	 * Create an instance of the Kohana annotation driver, which supports loading model files from across the CFS. Also
	 * handles registering the Doctrine annotations in the Annotation registry - which would normally be handled by
	 * Doctrine\ORM\Configuration::newDefaultAnnotationDriver.
	 *
	 * @return \Doctrine\ORM\Mapping\Driver\AnnotationDriver
	 * @see Doctrine\ORM\Configuration::newDefaultAnnotationDriver
	 */
	protected function create_annotation_driver()
	{
		$config = $this->config->load('doctrine');

		// Register the Doctrine annotations
		$vendor_path = $config->get('composer_vendor_path');
		AnnotationRegistry::registerFile($vendor_path.'doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

		if ($config->get('use_simple_annotation_reader'))
		{
			// Register the ORM Annotations in the AnnotationReader
			$reader = new SimpleAnnotationReader();
			$reader->addNamespace('Doctrine\ORM\Mapping');
		}
		else
		{
			$reader = new AnnotationReader();
		}

		$cachedReader = new CachedReader($reader, new ArrayCache());

		return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($cachedReader, Kohana::include_paths());
	}

}
