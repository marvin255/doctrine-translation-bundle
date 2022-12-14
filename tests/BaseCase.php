<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Tests;

use Marvin255\DoctrineTranslationBundle\ClassNameManager\ClassNameManager;
use Marvin255\DoctrineTranslationBundle\Entity\Translatable;
use Marvin255\DoctrineTranslationBundle\Entity\Translation;
use Marvin255\DoctrineTranslationBundle\Locale\Locale;
use Marvin255\DoctrineTranslationBundle\Locale\LocaleProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for all tests.
 */
abstract class BaseCase extends TestCase
{
    public const BASE_LOCALE = 'en-US';
    public const BASE_LOCALE_ARRAY = [self::BASE_LOCALE];
    public const BASE_TRANSLATABLE_CLASS = 'Translatable';
    public const BASE_TRANSLATION_CLASS = 'Translation';
    public const BASE_CLASS_NAMES_MAP = [self::BASE_TRANSLATABLE_CLASS => self::BASE_TRANSLATION_CLASS];

    /**
     * @return Translatable&MockObject
     */
    protected function createTranslatableMock(): Translatable
    {
        /** @var Translatable&MockObject */
        $translatable = $this->getMockBuilder(Translatable::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $translatable;
    }

    /**
     * @return Translation&MockObject
     */
    protected function createTranslationMock(?Translatable $translatable = null, ?Locale $locale = null, ?int $id = null): Translation
    {
        /** @var Translation&MockObject */
        $translation = $this->getMockBuilder(Translation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translation->method('getTranslatable')->willReturn($translatable);
        $translation->method('getLocale')->willReturn($locale);
        $translation->method('getId')->willReturn($id);

        return $translation;
    }

    protected function createLocaleMock(string $localeString = self::BASE_LOCALE): Locale
    {
        /** @var Locale&MockObject */
        $locale = $this->getMockBuilder(Locale::class)
            ->disableOriginalConstructor()
            ->getMock();

        $locale->method('getFull')->willReturn($localeString);
        $locale->method('equals')->willReturnCallback(
            fn (Locale $toTest): bool => $toTest->getFull() === $localeString
        );

        return $locale;
    }

    protected function createBasicClassNameManagerMock(?Translatable $translatable = null, ?Translation $translation = null): ClassNameManager
    {
        $emtityClassMap = [];
        if ($translatable) {
            $emtityClassMap[self::BASE_TRANSLATABLE_CLASS] = $translatable;
        }
        if ($translation) {
            $emtityClassMap[self::BASE_TRANSLATION_CLASS] = $translation;
        }

        return $this->createClassNameManagerMock(self::BASE_CLASS_NAMES_MAP, $emtityClassMap);
    }

    protected function createLocaleProviderMock(string $current = self::BASE_LOCALE, string $default = self::BASE_LOCALE): LocaleProvider
    {
        /** @var LocaleProvider&MockObject */
        $localeProvider = $this->getMockBuilder(LocaleProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $localeProvider->method('getCurrentLocale')->willReturn($this->createLocaleMock($current));
        $localeProvider->method('getDefaultLocale')->willReturn($this->createLocaleMock($default));

        return $localeProvider;
    }

    /**
     * @psalm-param array<string, string> $translatableTranslationPairs
     * @psalm-param array<string, object|object[]> $entityClassMap
     *
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    protected function createClassNameManagerMock(array $translatableTranslationPairs = [], array $entityClassMap = []): ClassNameManager
    {
        /** @var ClassNameManager&MockObject */
        $manager = $this->getMockBuilder(ClassNameManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->method('isTranslationClass')->willReturnCallback(
            fn (string $toCheck): bool => \in_array($toCheck, $translatableTranslationPairs)
        );

        $manager->method('isTranslatableClass')->willReturnCallback(
            fn (string $toCheck): bool => \array_key_exists($toCheck, $translatableTranslationPairs)
        );

        $manager->method('getTranslationClassForTranslatable')->willReturnCallback(
            fn (string $translatable): string => $translatableTranslationPairs[$translatable] ?? ''
        );

        $manager->method('getTranslatableClassForTranslation')->willReturnCallback(
            fn (string $translation): string => array_search($translation, $translatableTranslationPairs) ?: ''
        );

        $getClassForObject = function (object $entity) use ($entityClassMap): string {
            $result = '';
            foreach ($entityClassMap as $classKey => $objects) {
                if ($objects === $entity || (\is_array($objects) && \in_array($entity, $objects, true))) {
                    $result = $classKey;
                    break;
                }
            }

            return $result;
        };

        $manager->method('getTranslationClassForTranslatableEntity')->willReturnCallback(
            fn (object $entity): string => $translatableTranslationPairs[$getClassForObject($entity)] ?? ''
        );

        $manager->method('areItemsClassesRelated')->willReturnCallback(
            function (mixed $translatable, mixed $translation) use ($getClassForObject, $translatableTranslationPairs): bool {
                $translatableClass = \is_object($translatable) ? $getClassForObject($translatable) : '';
                $translationClass = \is_object($translation) ? $getClassForObject($translation) : '';

                return isset($translatableTranslationPairs[$translatableClass])
                    && $translatableTranslationPairs[$translatableClass] === $translationClass;
            }
        );

        return $manager;
    }
}
