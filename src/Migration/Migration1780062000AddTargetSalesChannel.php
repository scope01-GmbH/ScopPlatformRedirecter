<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1780062000AddTargetSalesChannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780062000;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociative(
            "SHOW COLUMNS FROM `scop_platform_redirecter_redirect` LIKE 'target_sales_channel_id'"
        );

        if (\count($columns) === 0) {
            $connection->executeStatement(
                'ALTER TABLE `scop_platform_redirecter_redirect` ADD `target_sales_channel_id` BINARY(16) NULL AFTER `target_language_id`'
            );
        }
    }
}
