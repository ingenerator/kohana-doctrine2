<?php
/**
 * Tests the behaviour of the Doctrine_KohanaAnnotationDriver
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2013 inGenerator Ltd
 * @licence   BSD
 */
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Tests the behaviour of the Doctrine_KohanaAnnotationDriver
 *
 * @covers    Doctrine_KohanaAnnotationDriver
 * @group     doctrine
 * @group     doctrine.annotationdriver
 */
class Doctrine_KohanaAnnotationDriverTest extends Kohana_Unittest_TestCase {

	/**
	 * The standard Doctrine Annotation driver will choke on the CFS because it requires each of the files, which will
	 * result in duplicate class definitions. The Kohana driver overrides the class loader to list only active classes
	 * found within the CFS.
	 *
	 * Classes within the model path that are not tagged as Doctrine entities will be skipped.
	 *
	 * @return void
	 * @covers Doctrine_KohanaAnnotationDriver::getAllClassNames
	 */
	public function test_get_all_class_names_returns_cfs_classes_with_entity_tags()
	{
		$reader = new SimpleAnnotationReader();
		$reader->addNamespace('Doctrine\ORM\Mapping');

		$driver = new Doctrine_KohanaAnnotationDriver(
			new CachedReader($reader, new ArrayCache()),
			Kohana::include_paths()
		);

		$this->assertEquals(
			array(
			     'Model_Module1_Nested',
			     'Model_Module1Simple',
			     'Model_Module2Simple',
			     'Model_Module2Transparent'
			),
			$driver->getAllClassNames()
		);
	}

	/**
	 * @var array stores the active Kohana modules as they were before these tests
	 */
	public static $old_modules = array();

	/**
	 * Temporarily add the test module1 and module2 directories to the module list
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		self::$old_modules = Kohana::modules();
		$modules = self::$old_modules;
		$modules['module1'] = realpath(__DIR__.'/../test_data/module1');
		$modules['module2'] = realpath(__DIR__.'/../test_data/module2');
		Kohana::modules($modules);
	}

	/**
	 * Restore the module list to what it was before these tests
	 *
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		Kohana::modules(self::$old_modules);
	}

}