<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Marvin255\DoctrineTranslationBundle\ClassNameManager\ClassNameManager;
use Marvin255\DoctrineTranslationBundle\Entity\Translatable;
use Marvin255\DoctrineTranslationBundle\Entity\Translation;
use Marvin255\DoctrineTranslationBundle\Locale\Locale;
use Marvin255\DoctrineTranslationBundle\Locale\LocaleFactory;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Repository that can query translations for items.
 */
class TranslationRepository
{
    public const QUERY_ALIAS = 't';

    private readonly EntityManagerInterface $em;

    private readonly LocaleSwitcher $localeSwitcher;

    private readonly ClassNameManager $classNameManager;

    public function __construct(
        EntityManagerInterface $em,
        LocaleSwitcher $localeSwitcher,
        ClassNameManager $classNameManager
    ) {
        $this->em = $em;
        $this->localeSwitcher = $localeSwitcher;
        $this->classNameManager = $classNameManager;
    }

    /**
     * Searches translations related for set list of items and current app locale.
     *
     * @param iterable<Translatable>|Translatable $items
     *
     * @return iterable<Translation>
     */
    public function findTranslationForCurrentLocale(iterable|Translatable $items): iterable
    {
        $currentLocale = LocaleFactory::create($this->localeSwitcher->getLocale());

        return $this->findTranslations($items, $currentLocale);
    }

    /**
     * Searches translations related for set list of items.
     * If locales set then translations will be load only for that locales.
     *
     * @param iterable<Translatable>|Translatable $items
     * @param iterable<Locale>|Locale             $locales
     *
     * @return iterable<Translation>
     */
    public function findTranslations(iterable|Translatable $items, iterable|Locale $locales = []): iterable
    {
        $itemsByClasses = $this->groupItemsByTranslationClass($items);

        if (empty($itemsByClasses)) {
            return [];
        }

        $localesStrings = $this->getLocaleStringsFromLocales($locales);

        $result = [];
        foreach ($itemsByClasses as $translationClass => $translatableItems) {
            $qb = $this->em->createQueryBuilder();
            $qb->select(self::QUERY_ALIAS);
            $qb->from($translationClass, self::QUERY_ALIAS);
            $qb->where(self::QUERY_ALIAS . '.' . Translation::TRANSLATABLE_FIELD_NAME . ' IN (:translatables)');
            $qb->setParameter('translatables', $translatableItems);
            if (!empty($localesStrings)) {
                $qb->andWhere(self::QUERY_ALIAS . '.' . Translation::LOCALE_FIELD_NAME . ' IN (:locales)');
                $qb->setParameter('locales', $localesStrings);
            }
            /** @var Translation[] */
            $tmpResult = $qb->getQuery()->getResult();
            $result = array_merge($result, $tmpResult);
        }

        return $result;
    }

    /**
     * Uses list of translations to set current translation for all translatable items.
     *
     * @param iterable<Translatable>|Translatable $items
     * @param iterable<Translation>|Translation   $translations
     */
    public function setCurrentTranslation(iterable|Translatable $items, iterable|Translation $translations): void
    {
        $items = $items instanceof Translatable ? [$items] : $items;
        $translations = $translations instanceof Translation ? [$translations] : $translations;

        foreach ($items as $item) {
            $currentTranslation = null;
            foreach ($translations as $translation) {
                $parentTranslatable = $translation->getTranslatable();
                if ($parentTranslatable !== null && $this->isTranslatablesEqual($item, $parentTranslatable)) {
                    $currentTranslation = $translation;
                    break;
                }
            }
            $item->setCurrentTranslation($currentTranslation);
        }
    }

    /**
     * Groups translatable items by translation class for search.
     *
     * @param iterable<Translatable>|Translatable $items
     *
     * @return array<string, Translatable[]>
     */
    private function groupItemsByTranslationClass(iterable|Translatable $items): array
    {
        $items = $items instanceof Translatable ? [$items] : $items;

        $itemsByClasses = [];
        foreach ($items as $item) {
            $translationClass = $this->classNameManager->getTranslationClassForTranslatableEntity($item);
            $itemsByClasses[$translationClass][] = $item;
        }

        return $itemsByClasses;
    }

    /**
     * Convert list of Locale objects to list of strings.
     *
     * @param iterable<Locale>|Locale $locales
     *
     * @return string[]
     */
    private function getLocaleStringsFromLocales(iterable|Locale $locales): array
    {
        $locales = $locales instanceof Locale ? [$locales] : $locales;

        $localesStrings = [];
        foreach ($locales as $locale) {
            $localeString = $locale->getFull();
            if (!\in_array($localeString, $localesStrings)) {
                $localesStrings[] = $localeString;
            }
        }

        return $localesStrings;
    }

    /**
     * Compares two translatable items for equality.
     */
    private function isTranslatablesEqual(Translatable $a, Translatable $b): bool
    {
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
