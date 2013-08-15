<?php
/**
 * Tests the behaviour of the Doctrine_EMFactory
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2013 inGenerator Ltd
 * @licence   BSD
 */

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

}