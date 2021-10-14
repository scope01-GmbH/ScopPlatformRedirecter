<?php
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

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

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\DataAbstractionLayer\EntityCollection::getExpectedClass()
     */
    protected function getExpectedClass(): string
    {
        return Redirect::class;
    }
}
