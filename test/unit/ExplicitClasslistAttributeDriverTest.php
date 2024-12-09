<?php

namespace test\unit\Ingenerator\KohanaDoctrine;


use BadMethodCallException;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\MappingException;
use Ingenerator\KohanaDoctrine\ExplicitClasslistAttributeDriver;
use PHPUnit\Framework\TestCase;

class ExplicitClasslistAttributeDriverTest extends TestCase
{
    protected array $classes = [];

    public function test_is_initialisable_annotation_driver()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(ExplicitClasslistAttributeDriver::class, $subject);
        $this->assertInstanceOf(AttributeDriver::class, $subject);
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

    public function test_its_get_classes_throws_if_configured_class_does_not_exist()
    {
        $this->classes = ['Any\Class\That\Does\Not\Exist'];
        $this->expectException(MappingException::class);
        $this->newSubject()->getAllClassNames();
    }

    public function test_it_throws_from_add_paths()
    {
        $this->expectException(BadMethodCallException::class);
        $this->newSubject()->addPaths([__DIR__]);
    }

    public function test_it_throws_from_get_paths()
    {
        $this->expectException(BadMethodCallException::class);
        $this->newSubject()->getPaths();
    }

    protected function newSubject()
    {
        return new ExplicitClasslistAttributeDriver($this->classes);
    }

}

class AnyEntity
{

}

class AnyOtherEntity
{

}
