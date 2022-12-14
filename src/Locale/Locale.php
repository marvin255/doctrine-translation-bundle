<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Locale;

/**
 * Simple object that provides locale value for doctrine.
 */
class Locale
{
    private readonly string $language;

    private readonly string $region;

    private readonly string $full;

    /**
     * @internal please use LocaleFactory to create locale
     */
    public function __construct(string $locale)
    {
        $trimmedLocale = trim($locale);
        if ($trimmedLocale === '') {
            throw new \InvalidArgumentException("Locale can't be an empty string");
        }

        $arLocale = explode('-', $trimmedLocale);

        $this->language = strtolower(trim($arLocale[0] ?? ''));
        $this->region = strtoupper(trim($arLocale[1] ?? ''));
        $this->full = $this->language . ($this->region === '' ? '' : "-{$this->region}");
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getFull(): string
    {
        return $this->full;
    }

    public function equals(mixed $locale): bool
    {
        return $locale instanceof Locale && $this->full === $locale->getFull();
    }

    public function __toString(): string
    {
        return $this->full;
    }
}
