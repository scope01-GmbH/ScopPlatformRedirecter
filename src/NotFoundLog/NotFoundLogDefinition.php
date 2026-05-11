<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\NotFoundLog;

use Scop\PlatformRedirecter\Redirect\RedirectDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class NotFoundLogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'scop_platform_redirecter_404';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return NotFoundLogCollection::class;
    }

    public function getEntityClass(): string
    {
        return NotFoundLog::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('url', 'url', 500))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            (new IntField('hit_count', 'hitCount'))->addFlags(new Required()),
            (new DateTimeField('last_hit_at', 'lastHitAt'))->addFlags(new Required()),
            (new JsonField('referers', 'referers'))->addFlags(new ApiAware()),
            (new FkField('redirect_id', 'redirectId', RedirectDefinition::class))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('redirect', 'redirect_id', RedirectDefinition::class, 'id', false),
            new BoolField('ignored', 'ignored'),
        ]);
    }
}
