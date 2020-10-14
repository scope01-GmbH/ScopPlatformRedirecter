<?php
declare(strict_types = 1);
namespace Scop\PlatformRedirecter\Redirect;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void          add(Redirect $entity)
 * @method void          set(string $key, Redirect $entity)
 * @method Redirect[]    getIterator()
 * @method Redirect[]    getElements()
 * @method Redirect|null get(string $key)
 * @method Redirect|null first()
 * @method Redirect|null last()
 */
class RedirectCollection extends EntityCollection
{

    protected function getExpectedClass(): string
    {
        return Redirect::class;
    }
}

