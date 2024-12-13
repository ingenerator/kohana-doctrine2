<?php

namespace test\unit\Ingenerator\KohanaDoctrine\EntityManagerUtils;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\MappingException;
use Ingenerator\KohanaDoctrine\Dependency\ConnectionConfigProvider;
use Ingenerator\KohanaDoctrine\Dependency\DoctrineFactory;
use Ingenerator\KohanaDoctrine\EntityManagerUtils\EntityDetacher;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EntityDetacherTest extends TestCase
{

    public function test_it_does_nothing_with_no_entities()
    {
        $em = $this->createEntityManager();

        EntityDetacher::detachAllOfType($em, TestEntityOne::class);
        $this->assertSame([], $em->getUnitOfWork()->getIdentityMap());
    }

    public function test_it_detaches_all_persisted_entities_of_expected_type()
    {
        $entities = [
            'e1' => new TestEntityOne(1),
            'e2' => new TestEntityOne(5),
            'e3' => new TestEntityOne(123),
        ];

        $em = $this->createEntityManagerWithManagedEntities(...$entities);

        EntityDetacher::detachAllOfType($em, TestEntityOne::class);

        $this->assertEntityPersistenceState(
            array_fill_keys(array_keys($entities), FALSE),
            $em,
            $entities,
            'All entities should be detached'
        );
    }

    public function test_it_only_detaches_the_specified_type()
    {
        $entities = [
            'e1-1' => new TestEntityOne(1),
            'e2-1' => new TestEntityTwo(1),
            'e1-2' => new TestEntityOne(2),
            'e1-3' => new TestEntityOne(3),
            'e2-2' => new TestEntityTwo(2),
            'e2-3' => new TestEntityTwo(3),
        ];

        $em = $this->createEntityManagerWithManagedEntities(...$entities);

        EntityDetacher::detachAllOfType($em, TestEntityOne::class);

        $this->assertEntityPersistenceState(
            [
                'e1-1' => FALSE,
                'e2-1' => TRUE,
                'e1-2' => FALSE,
                'e1-3' => FALSE,
                'e2-2' => TRUE,
                'e2-3' => TRUE,
            ],
            $em,
            $entities,
            'Should have detached expected entities'
        );
    }

    public function test_it_detaches_entities_even_if_they_have_not_been_flushed_and_ids_generated()
    {
        // The snippet at https://github.com/doctrine/orm/issues/8460 only detaches entities where an ID has already
        // been generated. We really want to clear everything of the type, even if it has not yet been flushed to the
        // database.
        $entities = [
            'e1-new-1' => new TestEntityWithGeneratedId(NULL),
            'e2-1' => new TestEntityTwo(1),
            'e1-new-2' => new TestEntityWithGeneratedId(NULL),
            'e1-existing' => new TestEntityWithGeneratedId(1),
        ];

        $em = $this->createEntityManagerWithManagedEntities(...$entities);

        EntityDetacher::detachAllOfType($em, TestEntityWithGeneratedId::class);

        $this->assertEntityPersistenceState(
            [
                'e1-new-1' => FALSE,
                'e2-1' => TRUE,
                'e1-new-2' => FALSE,
                'e1-existing' => FALSE,
            ],
            $em,
            $entities,
            'Should have detached expected entities'
        );
    }

    public function test_it_throws_if_class_is_a_base_class()
    {
        $em = $this->createEntityManagerWithManagedEntities(new TestChildEntityOne(15));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TestParentEntity as it has child entities');

        EntityDetacher::detachAllOfType($em, TestParentEntity::class);
    }

    public function test_it_throws_if_class_is_not_an_entity()
    {
        $em = $this->createEntityManager();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('not a valid entity');
        EntityDetacher::detachAllOfType($em, self::class);
    }

    private function createEntityManager(): EntityManager
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([]));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxies');

        return DoctrineFactory::buildEntityManager(new ConnectionConfigProvider(), $config, new EventManager());
    }

    private function assertEntityPersistenceState(
        array $expected,
        EntityManager $em,
        array $entities,
        string $message
    ): void {
        $this->assertSame(
            $expected,
            array_map(
                fn($e) => $em->contains($e),
                $entities
            ),
            $message
        );
    }

    private function createEntityManagerWithManagedEntities(
        TestEntityBaseWithId|TestEntityWithGeneratedId ...$entities
    ): EntityManager {
        $em = $this->createEntityManager();
        foreach ($entities as $entity) {
            if ($entity->id) {
                // Simulate this being already managed e.g. hydrated by the entity manager
                $em->getUnitOfWork()->registerManaged($entity, ['id' => $entity->id], (array) $entity);
            } else {
                // Simulate this being persisted as a new entity
                $em->persist($entity);
            }
        }

        $this->assertEntityPersistenceState(
            array_fill_keys(array_keys($entities), TRUE),
            $em,
            $entities,
            'All entities should be persisted at the start of the test'
        );

        return $em;
    }
}

class TestEntityBaseWithId
{
    public function __construct(
        #[Id]
        #[Column]
        public readonly int $id
    ) {
    }
}

#[Entity]
class TestEntityOne extends TestEntityBaseWithId
{
}

#[Entity]
class TestEntityTwo extends TestEntityBaseWithId
{
}

#[Entity]
class TestEntityWithGeneratedId
{

    public function __construct(
        #[Id]
        #[Column]
        #[GeneratedValue]
        public ?int $id = NULL
    ) {
    }
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorMap(['one' => TestChildEntityOne::class])]
abstract class TestParentEntity extends TestEntityBaseWithId
{

}

#[Entity]
class TestChildEntityOne extends TestParentEntity
{

}
