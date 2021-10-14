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

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;

class RedirectDefinition extends EntityDefinition
{

    /**
     * @var string
     */
    public const ENTITY_NAME = 'scop_platform_redirecter_redirect';

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition::getEntityName()
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition::getCollectionClass()
     */
    public function getCollectionClass(): string
    {
        return RedirectCollection::class;
    }

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition::getEntityClass()
     */
    public function getEntityClass(): string
    {
        return Redirect::class;
    }

    /**
     * {@inheritDoc}
     * @see \Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition::defineFields()
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField("sourceURL", "sourceURL"),
            new StringField("targetURL", "targetURL"),
            new IntField("httpCode", "httpCode"),
            new BoolField("enabled", "enabled")
        ]);
    }
}
