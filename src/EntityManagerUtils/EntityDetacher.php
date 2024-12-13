<?php

namespace Ingenerator\KohanaDoctrine\EntityManagerUtils;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class EntityDetacher
{

    /**
     * Detach all managed entities which have the (exact) specified class
     *
     * This is a replacement for the old $entityManager->clear($class) in doctrine/persistence <= 2.
     */
    public static function detachAllOfType(
        EntityManagerInterface $em,
        string $entity_class
    ): void {
        $meta = $em->getClassMetadata($entity_class);
        if ($meta->subClasses !== []) {
            // We can't safely work out which they want to clear (the IdentityMap is organised by concrete class names,
            // and it is too much runtime work to check all possible children). Callers will just need to be explicit
            // about which classes should be detached.
            throw new InvalidArgumentException(
                sprintf(
                    "Cannot call %s with %s as it has child entities. Instead, call it for each subclass you wish to clear.",
                    __METHOD__,
                    $entity_class
                )
            );
        }

        // The code snippet at https://github.com/doctrine/orm/issues/8460 doesn't cater for entities that were
        // persisted with an empty database-generated ID and have not yet been flushed. We want to be certain that
        // we're detaching *all* the entities of this type.
        foreach ($em->getUnitOfWork()->getScheduledEntityInsertions() as $insertion) {
            // Strict compare on class name, rather than instanceof, as these should only be concrete entity types
            if ($insertion::class === $entity_class) {
                $em->detach($insertion);
            }
        }


        $entities = $em->getUnitOfWork()->getIdentityMap()[$entity_class] ?? [];
        foreach ($entities as $entity) {
            $em->detach($entity);
        }
    }
}
