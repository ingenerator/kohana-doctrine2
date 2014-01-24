<?php
/**
 * Tests the behaviour of the Doctrine_EMFactory
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2013 inGenerator Ltd
 * @licence   BSD
 */
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Tests the behaviour of the Doctrine_EMFactory
 *
 * @covers    Doctrine_EMFactory
 * @group     doctrine
 * @group     doctrine.emfactory
 */
class Doctrine_EMFactoryTest extends Kohana_Unittest_TestCase {

	/**
	 * The factory should create an entity manager instance
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_creates_entity_manager()
	{
		$factory = new Doctrine_EMFactory();
		$this->assertInstanceOf('Doctrine\ORM\EntityManager', $factory->entity_manager());
	}

	/**
	 * The proxy directory and proxy namespace should be configurable
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_sets_proxy_details_from_config()
	{
		$config = $this->mock_config_values(array(
			'database' => array(),
			'doctrine' => array(
				'proxy_dir'       => APPPATH.'classes/My/App/Proxies',
				'proxy_namespace' => 'My/App/Proxies'
			)
		));

		$factory = new Doctrine_EMFactory($config);
		$em = $factory->entity_manager();
		$config = $em->getConfiguration();

		$this->assertEquals(APPPATH.'classes/My/App/Proxies', $config->getProxyDir());
		$this->assertEquals('My/App/Proxies', $config->getProxyNamespace());
	}

	/**
	 * Data provider for test_sets_autogenerate_proxies_from_environment
	 *
	 * @return array the test cases
	 */
	public function provider_environment_autogenerate_proxies()
	{
		return array(
			array(Kohana::DEVELOPMENT, TRUE),
			array(Kohana::TESTING, FALSE),
			array(Kohana::PRODUCTION, FALSE),
		);
	}

	/**
	 * Proxies should be automatically built in a development environment but otherwise only generated on request
	 *
	 * @param int  $environment         One of the Kohana environment constants
	 * @param bool $expect_autogenerate Expected value of autogenerate proxies in this environment
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @dataProvider provider_environment_autogenerate_proxies
	 * @return void
	 */
	public function test_sets_autogenerate_proxies_from_environment($environment, $expect_autogenerate)
	{
		$factory = new Doctrine_EMFactory(NULL, $environment);
		$em = $factory->entity_manager();
		$config = $em->getConfiguration();

		$this->assertEquals($expect_autogenerate, $config->getAutoGenerateProxyClasses());
	}

	/**
	 * In development, use an array cache (per-request caching only) for query and metadata caching so that changes
	 * are reflected immediately.
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_sets_array_cache_in_development_environment()
	{
		$factory = new Doctrine_EMFactory(NULL, Kohana::DEVELOPMENT);
		$em = $factory->entity_manager();

		$this->assertInstanceOf('\Doctrine\Common\Cache\ArrayCache', $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf('\Doctrine\Common\Cache\ArrayCache', $em->getConfiguration()->getQueryCacheImpl());
	}

	/**
	 * Outside development, use an APC cache. Ultimately this should be refactored out to allow service injection of
	 * alternative caches.
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_sets_apc_cache_outside_development()
	{
		$factory = new Doctrine_EMFactory(NULL, Kohana::PRODUCTION);
		$em = $factory->entity_manager();

		$this->assertInstanceOf('\Doctrine\Common\Cache\ApcCache', $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf('\Doctrine\Common\Cache\ApcCache', $em->getConfiguration()->getQueryCacheImpl());
	}

	/**
	 * Provider for the test_configures_database_from_kohana_configuration tests
	 *
	 * @return array the test cases
	 */
	public function provider_database_config_groups()
	{
		return array(
			array('default', 'my.db.host'),
			array('remote', 'remote.db.host')
		);
	}

	/**
	 * Should use the Kohana database configuration for the entity manager so that the same config settings can be
	 * shared between Doctrine and non-doctrine code.
	 *
 	 * @param $group_name
	 * @param $expect_host
	 *
	 * @dataProvider provider_database_config_groups
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_configures_database_from_kohana_configuration($group_name, $expect_host)
	{
		$config = $this->mock_config_values(array(
			'doctrine' => array(),
		    'database' => array(
			    $group_name => array(
				    'type' => 'MySQL',
				    'connection' => array(
					    'hostname' => $expect_host,
					    'database' => 'doctrine2',
					    'username' => 'testuser',
					    'password' => 'testpassword',
					    'persistent' => TRUE
				    ),
				    'charset' => 'utf8'
			    )
		    )
		));

		$factory = new Doctrine_EMFactory($config);
		$em = $factory->entity_manager($group_name);
		$connection = $em->getConnection();

		$this->assertEquals($expect_host, $connection->getHost());
		$this->assertEquals('doctrine2', $connection->getDatabase());
		$this->assertEquals('testuser', $connection->getUsername());
		$this->assertEquals('testpassword', $connection->getPassword());
		// It's not possible to assert the character set
	}

	/**
	 * For now, mapping all the Kohana driver types and options to Doctrine driver types and options is too much - so
	 * just throw an exception if the type is other than MySQL
	 *
	 * @expectedException InvalidArgumentException
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_only_supports_mysql_for_now()
	{
		$config = $this->mock_config_values(array(
			'doctrine' => array(),
		    'database' => array(
			    'default' => array(
				    'type' => 'PDO'
			    )
		    )
		));

		$factory = new Doctrine_EMFactory($config);
		$em = $factory->entity_manager();
	}

	/**
	 * Should use a Kohana-specific annotation driver to support loading model classes from across the CFS
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_registers_the_kohana_annotation_driver()
	{
		$factory = new Doctrine_EMFactory();
		$em = $factory->entity_manager();

		$this->assertInstanceOf('Doctrine_KohanaAnnotationDriver', $em->getConfiguration()->getMetadataDriverImpl());
	}

	/**
	 * Because we don't use Doctrine's core newDefaultAnnotationDriver method, we are responsible for registering the
	 * valid annotations in the AnnotationRegistry.
	 *
	 * @covers Doctrine_EMFactory::entity_manager
	 * @return void
	 */
	public function test_registers_mappings_in_the_annotation_registry()
	{
		$factory = new Doctrine_EMFactory();
		$em = $factory->entity_manager();

		$this->assertTrue(class_exists('\Doctrine\ORM\Mapping\Entity', FALSE));
	}

	public function test_registers_custom_types_from_config()
	{
		$config = $this->mock_config_values(array(
			'doctrine' => array(
				'custom_types' => array(
					'foo' => 'FooType'
				)
			),
		    'database' => array()
		));

		$factory = new Doctrine_EMFactory($config);
		$em = $factory->entity_manager();

		$this->assertInstanceOf('FooType', Type::getType('foo'));
	}

	/**
	 * Gets a stub Config object with at least the specified values. The provided values are merged with the existing
	 * configuration for each group to improve clarity of tests which only have to specify config values relevant to
	 * their assertions.
	 *
	 * [!!] Note that the stubbed Config will only contain values for groups that are explicitly set.
	 *
	 *     // In config/foo.php
	 *     return array(
	 *         'bar' => 'barvalue',
	 *         'foo' => 'foovalue'
	 *     );
	 *
	 *     // In test case
	 *     $config = $this->mock_config_values(array(
	 *         'foo' => array(
	 *             'bar' => 'testvalue'
	 *         )
	 *     );
	 *
	 *     print_r($config->load('foo')->getArrayCopy());
	 *
	 *     //
	 *     // Array
	 *     // (
	 *     //     [bar] => testvalue
	 *     //     [foo] => foovalue
	 *     // )
	 *
	 *
	 * @param array $grouped_values array of config values to explicitly set
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject the stubbed Kohana::$config replacement
	 */
	protected function mock_config_values(array $grouped_values)
	{
		// Create a mock Config instance
		$mock_config = $this->getMock('Config', array(), array(), '', FALSE, FALSE);

		// Build an array of config groups to return from the mock Config
		$config_groups = array();
		foreach ($grouped_values as $group => $values)
		{
			// Get default config for the group from the config loader
			$original_config = Kohana::$config->load($group)->as_array();

			// Merge with the provided values
			$new_config = Arr::merge($original_config, $values);

			// Create a new Config_Group and map as a return value for the stub
			$config_groups[] = array($group, new Config_Group($mock_config, $group, $new_config));
		}

		// Configure the mocked config load method to return the specified groups
		$mock_config->expects($this->any())
		        ->method('load')
		        ->will($this->returnValueMap($config_groups));
		return $mock_config;
	}

}


class FooType extends Type
{
	/**
	 * Gets the SQL declaration snippet for a field of this type.
	 *
	 * @param array            $fieldDeclaration The field declaration.
	 * @param AbstractPlatform $platform         The currently used database platform.
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		// TODO: Implement getSQLDeclaration() method.
	}

	/**
	 * Gets the name of this type.
	 *
	 * @return string
	 * @todo Needed?
	 */
	public function getName()
	{
		// TODO: Implement getName() method.
	}

}
