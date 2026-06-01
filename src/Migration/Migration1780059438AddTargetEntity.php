<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1780059438AddTargetEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780059438;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `scop_platform_redirecter_redirect`
            ADD `target_entity_type` VARCHAR(64) NULL AFTER `product_id`,
            ADD `target_entity_id` BINARY(16) NULL AFTER `target_entity_type`;
SQL;
        $connection->executeStatement($sql);
    }
}
