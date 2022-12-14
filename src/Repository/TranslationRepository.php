<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Repository;

use Marvin255\DoctrineTranslationBundle\ClassNameManager\ClassNameManager;
use Marvin255\DoctrineTranslationBundle\Entity\Translatable;
use Marvin255\DoctrineTranslationBundle\Entity\Translation;
use Marvin255\DoctrineTranslationBundle\EntityManager\EntityManagerProvider;
use Marvin255\DoctrineTranslationBundle\Locale\Locale;
use Marvin255\DoctrineTranslationBundle\Locale\LocaleProvider;

/**
 * Repository that can query translations for items.
 */
class TranslationRepository
{
    public const QUERY_ALIAS = 't';

    private readonly EntityManagerProvider $em;

    private readonly LocaleProvider $localeProvider;

    private readonly ClassNameManager $classNameManager;

    public function __construct(
        EntityManagerProvider $em,
        LocaleProvider $localeProvider,
        ClassNameManager $classNameManager
    ) {
        $this->em = $em;
        $this->localeProvider = $localeProvider;
        $this->classNameManager = $classNameManager;
    }

    /**
     * Searches and sets translations related for set list of items and current app locale.
     * If there is no translation for current locale will fallback to default locale.
     *
     * @param iterable<Translatable>|Translatable $items
     */
    public function findAndSetTranslationForCurrentLocale(iterable|Translatable $items): void
    {
        $locales = [
            $this->localeProvider->getCurrentLocale(),
            $this->localeProvider->getDefaultLocale(),
        ];

        $translations = $this->findTranslations($items, $locales);

        $this->setItemsTranslated($items, $translations, $this->localeProvider->getDefaultLocale());
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
        return $this->findTranslations($items, $this->localeProvider->getCurrentLocale());
    }

    /**
     * Searches and sets translations related for set list of items and set locale.
     *
     * @param iterable<Translatable>|Translatable $items
     * @param Locale                              $locale
     */
    public function findAndSetTranslationForLocale(iterable|Translatable $items, Locale $locale): void
    {
        $this->setItemsTranslated(
            $items,
            $this->findTranslations($items, $locale)
        );
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
            $qb = $this->em->createQueryBuilder($translationClass);
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
     * Uses list of translations to set current translations for all translatable items.
     *
     * @param iterable<Translatable>|Translatable $items
     * @param iterable<Translation>|Translation   $translations
     * @param Locale|null                         $fallbackLocale
     */
    public function setItemsTranslated(iterable|Translatable $items, iterable|Translation $translations, ?Locale $fallbackLocale = null): void
    {
        $items = $items instanceof Translatable ? [$items] : $items;
        $translations = $translations instanceof Translation ? [$translations] : $translations;

        foreach ($items as $item) {
            $translated = null;
            $fallbackTranslated = null;
            foreach ($translations as $translation) {
                if ($this->em->getEntityComparator()->isEqual($item, $translation->getTranslatable())) {
                    if ($translation->getLocale()?->equals($fallbackLocale) === true) {
                        $fallbackTranslated = $translation;
                    } else {
                        $translated = $translation;
                        break;
                    }
                }
            }
            $item->setTranslated($translated ?: $fallbackTranslated);
        }
    }

    /**
     * Groups translatable items by translation class for search.
     *
     * @param iterable<Translatable>|Translatable $items
     *
     * @return array<string, Translatable[]>
     *
     * @psalm-return array<class-string, Translatable[]>
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
            $localesStrings[] = $locale->getFull();
        }

        return array_unique($localesStrings);
    }
}
