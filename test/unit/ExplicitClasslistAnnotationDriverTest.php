<?php

namespace test\unit\Ingenerator\KohanaDoctrine;


use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\MappingException;
use Ingenerator\KohanaDoctrine\ExplicitClasslistAnnotationDriver;
use PHPUnit\Framework\TestCase;

class ExplicitClasslistAnnotationDriverTest extends TestCase
{
    /**
     * @var array
     */
    protected $classes = [];

    /**
     * @var
     */
    protected $reader;

    public function test_is_initialisable_annotation_driver()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(ExplicitClasslistAnnotationDriver::class, $subject);
        $this->assertInstanceOf(AnnotationDriver::class, $subject);
    }

    public function test_it_returns_injected_list_of_class_names()
    {
        $this->classes = [
            AnyEntity::class,
            AnyOtherEntity::class,
        ];
        $this->assertSame(
            $this->classes,
            $this->newSubject()->getAllClassNames()
        );
    }

    /**
     * @expectedException \Doctrine\Persistence\Mapping\MappingException
     */
    public function test_its_get_classes_throws_if_configured_class_does_not_exist()
    {
        $this->classes = ['Any\Class\That\Does\Not\Exist'];
        $this->newSubject()->getAllClassNames();
    }
    
    /**
     * @expectedException \BadMethodCallException
     */

    public function test_it_throws_from_add_paths()
    {
        $this->newSubject()->addPaths([__DIR__]);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function test_it_throws_from_get_paths()
    {
        $this->newSubject()->getPaths();
    }

    protected function setUp()
    {
        parent::setUp();
        $this->reader = $this->getMockBuilder(Reader::class)->getMock();
    }

    protected function newSubject()
    {
        return new ExplicitClasslistAnnotationDriver($this->reader, $this->classes);
    }

}

class AnyEntity
{

}

class AnyOtherEntity
{

}
