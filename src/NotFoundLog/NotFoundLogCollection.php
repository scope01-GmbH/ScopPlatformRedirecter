<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\NotFoundLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(NotFoundLog $entity)
 * @method void              set(string $key, NotFoundLog $entity)
 * @method NotFoundLog[]     getIterator()
 * @method NotFoundLog[]     getElements()
 * @method NotFoundLog|null  get(string $key)
 * @method NotFoundLog|null  first()
 * @method NotFoundLog|null  last()
 */
class NotFoundLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NotFoundLog::class;
    }
}
