<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\ClassNameManager;

use Marvin255\DoctrineTranslationBundle\Entity\Translatable;
use Marvin255\DoctrineTranslationBundle\Entity\Translation;
use Marvin255\DoctrineTranslationBundle\Exception\MappingException;

/**
 * Converts class names from translation to translatable and backward.
 */
class ClassNameManager
{
    private const TRANSLATION_CLASS_SUFFIX = 'Translation';

    /**
     * Check that class of the translation object is related to the class of the translatable object.
     */
    public function areItemsClassesRelated(mixed $translatable, mixed $translation): bool
    {
        if (!$this->isTranslatableEntity($translatable) || !$this->isTranslationEntity($translation)) {
            return false;
        }

        $relatedClass = $this->getTranslationClassForTranslatableEntity($translatable);

        return is_a($translation, $relatedClass);
    }

    /**
     * Check if set object is a translation item.
     *
     * @psalm-assert-if-true Translation $entity
     */
    public function isTranslationEntity(mixed $entity): bool
    {
        if (!\is_object($entity)) {
            return false;
        }

        $class = \get_class($entity);

        return $this->isTranslationClass($class);
    }

    /**
     * Check if set class name is a class name for translation item.
     */
    public function isTranslationClass(string $class): bool
    {
        return is_subclass_of($class, Translation::class);
    }

    /**
     * Check if set object is a translatable item.
     *
     * @psalm-assert-if-true Translatable $entity
     */
    public function isTranslatableEntity(mixed $entity): bool
    {
        if (!\is_object($entity)) {
            return false;
        }

        $class = \get_class($entity);

        return $this->isTranslatableClass($class);
    }

    /**
     * Check if set class name is a class name for translatable item.
     */
    public function isTranslatableClass(string $class): bool
    {
        return is_subclass_of($class, Translatable::class);
    }

    /**
     * Returns class name for translation related to set translatable object.
     *
     * @psalm-return class-string
     */
    public function getTranslationClassForTranslatableEntity(object $translatable): string
    {
        $className = \get_class($translatable);

        return $this->getTranslationClassForTranslatable($className);
    }

    /**
     * Returns class name for translation related to set translatable class name.
     *
     * @psalm-return class-string
     */
    public function getTranslationClassForTranslatable(string $translatableClass): string
    {
        if (!class_exists($translatableClass)) {
            throw new MappingException("Class '{$translatableClass}' doesn't exist");
        }

        $className = $translatableClass . self::TRANSLATION_CLASS_SUFFIX;

        if (!class_exists($className)) {
            throw new MappingException("Can't find '{$className}' for translatable '{$translatableClass}'");
        }

        if (!is_subclass_of($className, Translation::class)) {
            $requiredType = Translation::class;
            throw new MappingException("'{$className}' for translatable '{$translatableClass}' must extend '{$requiredType}'");
        }

        return $className;
    }

    /**
     * Returns class name for translation related to set translatable.
     *
     * @psalm-return class-string
     */
    public function getTranslatableClassForTranslation(string $translationClass): string
    {
        if (!class_exists($translationClass)) {
            throw new MappingException("Class '{$translationClass}' doesn't exist");
        }

        $suffix = self::TRANSLATION_CLASS_SUFFIX;
        if (!preg_match("/(.+){$suffix}$/", $translationClass, $matches)) {
            throw new MappingException("Class name '{$translationClass}' must end with '{$suffix}' suffix");
        }

        $className = $matches[1];

        if (!class_exists($className)) {
            throw new MappingException("Can't find '{$className}' for translation '{$translationClass}'");
        }

        if (!$this->isTranslatableClass($className)) {
            $requiredType = Translatable::class;
            throw new MappingException("'{$className}' for translation '{$translationClass}' must extends '{$requiredType}'");
        }

        return $className;
    }
}
