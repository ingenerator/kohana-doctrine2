<?php

namespace Ingenerator\KohanaDoctrine;


use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\MappingException;

class ExplicitClasslistAttributeDriver extends AttributeDriver
{
    /**
     * @param list<class-string>|null $entity_classes The list of entity class names. It is not expected to be nullable
     *                                                at runtime, but can take null to support testing when config is
     *                                                not fully defined.
     */
    public function __construct(?array $entity_classes = NULL)
    {
        parent::__construct([], reportFieldsWhereDeclared: TRUE);
        $this->classNames = $entity_classes ?? [];
    }

    public function getAllClassNames(): array
    {
        foreach ($this->classNames as $class_name) {
            if ( ! \class_exists($class_name)) {
                throw MappingException::nonExistingClass($class_name);
            }
        }

        return $this->classNames;
    }

    public function getPaths()
    {
        throw new \BadMethodCallException(__CLASS__.' does not support access to entity paths');
    }

    public function addPaths(array $paths): void
    {
        if ($paths === []) {
            // This is always called by the constructor as of doctrine/persistence@2.4.0
            return;
        }
        throw new \BadMethodCallException(__CLASS__.' does not support access to entity paths');
    }


}
