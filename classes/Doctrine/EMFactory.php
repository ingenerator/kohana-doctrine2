<?php
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

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
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function entity_manager()
	{
		$config = $this->config->load('doctrine');

		// Ensure the composer autoloader is registered
		require_once $config['composer_autoloader'];

		// Create the Configuration class
		$orm_config = new Configuration;

		// Create the metadata driver
		$driver = $orm_config->newDefaultAnnotationDriver(APPPATH.'/Model');
		$orm_config->setMetadataDriverImpl($driver);

		// Configure the proxy directory and namespace
		$orm_config->setProxyDir($config['proxy_dir']);
		$orm_config->setProxyNamespace($config['proxy_namespace']);

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

		// Create the Entity Manager
		$em = EntityManager::create(array('driver'=>'pdo_mysql'), $orm_config);
		return $em;
	}

}