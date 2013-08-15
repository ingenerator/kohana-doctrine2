<?php
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
	 * @param Config $config the Kohana config loader - loaded from Kohana::$config if required
	 */
	public function __construct(Config $config = NULL)
	{
		$this->config = ($config !== NULL) ? $config : Kohana::$config;
	}

	/**
	 * Creates a new EntityManager instance
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

		// Configure the proxy directory
		$orm_config->setProxyDir(APPPATH.'/Model/Proxy');
		$orm_config->setProxyNamespace('Model/Proxy');

		// Create the Entity Manager
		$em = EntityManager::create(array('driver'=>'pdo_mysql'), $orm_config);
		return $em;
	}

}