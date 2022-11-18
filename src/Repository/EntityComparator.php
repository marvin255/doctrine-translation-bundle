<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Compares two entites by strict comparision and identifiers equality.
 */
class EntityComparator
{
    private readonly EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Checks that two doctrin entities are equal.
     */
    public function isEqual(object $a, object $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $aClass = \get_class($a);
        $bClass = \get_class($b);

        if ($aClass !== $bClass) {
            return false;
        }

        $meta = $this->em->getClassMetadata($aClass);
        $aId = $meta->getIdentifierValues($a);
        $bId = $meta->getIdentifierValues($b);

        return $aId === $bId;
    }
}
