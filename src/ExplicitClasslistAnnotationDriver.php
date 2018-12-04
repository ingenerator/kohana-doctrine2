<?php

namespace Ingenerator\KohanaDoctrine;


use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

class ExplicitClasslistAnnotationDriver extends AnnotationDriver
{
    public function __construct(Reader $reader, array $entity_classes)
    {
        parent::__construct($reader, []);
        $this->classNames = $entity_classes;
    }

    public function getAllClassNames()
    {
        foreach ($this->classNames as $class_name) {
            if ( ! class_exists($class_name)) {
                throw MappingException::nonExistingClass($class_name);
            }
        }

        return $this->classNames;
    }

    public function getPaths()
    {
        throw new \BadMethodCallException(__CLASS__.' does not support access to entity paths');
    }

    public function addPaths(array $paths)
    {
        throw new \BadMethodCallException(__CLASS__.' does not support access to entity paths');
    }


}