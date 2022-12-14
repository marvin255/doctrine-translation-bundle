<?php

declare(strict_types=1);

namespace Marvin255\DoctrineTranslationBundle\Tests\Entity;

use Marvin255\DoctrineTranslationBundle\Entity\Translation;
use Marvin255\DoctrineTranslationBundle\Tests\BaseCase;

/**
 * @internal
 */
class TranslationTest extends BaseCase
{
    public function testGetIdDefault(): void
    {
        /** @var Translation */
        $model = $this->getMockForAbstractClass(Translation::class);

        $this->assertNull($model->getId());
    }

    public function testGetSetLocale(): void
    {
        $locale = $this->createLocaleMock();

        /** @var Translation */
        $model = $this->getMockForAbstractClass(Translation::class);

        $this->assertSame($model, $model->setLocale($locale));
        $this->assertSame($locale, $model->getLocale());
    }

    public function testGetSetTranslatable(): void
    {
        $translatable = $this->createTranslatableMock();

        /** @var Translation */
        $model = $this->getMockForAbstractClass(Translation::class);

        $this->assertSame($model, $model->setTranslatable($translatable));
        $this->assertSame($translatable, $model->getTranslatable());
    }
}
