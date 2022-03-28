<?php

namespace Ingenerator\KohanaDoctrine;


use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

class ExplicitClasslistAnnotationDriver extends AnnotationDriver
{
    /**
     * ExplicitClasslistAnnotationDriver constructor.
     *
     * @param Reader     $reader
     * @param array|NULL $entity_classes The list of entity class names
     *                                   [NB] it is not expected to be valid for this to be empty at runtime, but
     *                                   allowing a null value allows us to create an instance in development / test
     *                                   environments without full config.
     */
    public function __construct(Reader $reader, array $entity_classes = NULL)
    {
        parent::__construct($reader, []);
        $this->classNames = $entity_classes ?: [];
    }

    public function getAllClassNames()
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

    public function addPaths(array $paths)
    {
        if ($paths === []) {
            // This is always called by the constructor as of doctrine/persistence@2.4.0
            return;
        }
        throw new \BadMethodCallException(__CLASS__.' does not support access to entity paths');
    }


}
