<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Tests;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract class with event subscriber related helpers.
 */
abstract class EventSubscriberCase extends BaseCase
{
    protected function assertAssociationTarget(string $expected, LoadClassMetadataEventArgs $args, string $associationName): void
    {
        $associations = $args->getClassMetadata()->associationMappings;
        $this->assertSame($expected, $associations[$associationName]['targetEntity'] ?? '');
    }

    /**
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    protected function createEventArgsMock(array $data = []): LoadClassMetadataEventArgs
    {
        $name = (string) ($data['name'] ?? '');
        $table = (array) ($data['table'] ?? []);
        $associations = (array) ($data['associations'] ?? []);

        /** @var ClassMetadata&MockObject */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->method('getName')->willReturn($name);
        $metadata->method('getAssociationMappings')->willReturn($associations);
        $metadata->associationMappings = $associations;
        $metadata->table = $table;

        /** @var LoadClassMetadataEventArgs&MockObject */
        $argsMock = $this->getMockBuilder(LoadClassMetadataEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $argsMock->method('getClassMetadata')->willReturn($metadata);

        return $argsMock;
    }
}
